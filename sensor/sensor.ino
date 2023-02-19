#include <NTPClient.h>
#include <ESP8266WiFi.h>
#include <WiFiUdp.h>
#include "DHT.h"

#define DHTPIN 2        // what digital pin we're connected to
#define DHTTYPE DHT22   // DHT 22
#define ONEMINUTE 6e7   // 1 minute (in microseconds)
#define ONEHOUR 60      // 60 minutes in an hour
#define TIMEOUT 20000   // wait 20 seconds for timeout to website
#define DEBUG true     // display debug messages
#define MAXRETRIES 10
#define CMDWAIT 100
#define CMDSEND 101
#define MIDNIGHT 24
#define RTCMEMORYSTART 66

extern "C" {
#include "user_interface.h" // this is for the RTC memory read/write functions
}

typedef struct {
  int command = 000;
  int hour = -1;
} rtcStore;

rtcStore rtcMem;

const char* ssid = ***REMOVED***;
const char* password = ***REMOVED***;
const char* host = ***REMOVED***;
char url[46];
char temperature[7];
char humidity[7];
const char authorization_id[9] = "***REMOVED***";
const int greenhouse_id = 1;
double delayMinutes = 0;

DHT dht(DHTPIN, DHTTYPE);

void getSensorData(char *temp, char *humidity){
  // collect data
  float h = dht.readHumidity();
  float t = dht.readTemperature();
  dtostrf(t,5,2,temperature);
  dtostrf(h,5,2,humidity);
}

void readFromRTCMemory() {
  system_rtc_mem_read(RTCMEMORYSTART, &rtcMem, sizeof(rtcMem));
  #if DEBUG
    Serial.println("Reading from memory...");
    Serial.print("hour = ");
    Serial.println(rtcMem.hour);
    Serial.print("cmd = ");
    Serial.println(rtcMem.command);
  #endif
  yield();
}

void writeToRTCMemory() {
  system_rtc_mem_write(RTCMEMORYSTART, &rtcMem, 8);

  #if DEBUG
    Serial.println("Writing to memory...");
    Serial.print("hour = ");
    Serial.println(rtcMem.hour);
    Serial.print("cmd = ");
    Serial.println(rtcMem.command);
  #endif
  yield();
}

void startWiFi(){
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int count = 0;
  while (WiFi.status() != WL_CONNECTED && count <= MAXRETRIES) {
    delay(500);
    #if DEBUG
      Serial.print(".");
    #endif
    count ++;
  }

  // put the ESP to sleep after too many unsuccessful retries to conserve battery
  if (count == MAXRETRIES) ESP.deepSleepInstant(ONEHOUR * ONEMINUTE);

  #if DEBUG
    Serial.println("");
    Serial.print("Connected! IP address: ");
    Serial.println(WiFi.localIP());
  #endif
}


void getNTPTime(){
 
  WiFiUDP ntpUDP;
  NTPClient timeClient(ntpUDP, "europe.pool.ntp.org", 0);
  timeClient.begin();
  timeClient.update();

  #if DEBUG
    Serial.print("Current time is: ");
    Serial.print(timeClient.getHours());
    Serial.print(":");
    Serial.println(timeClient.getMinutes());
  #endif




  if ((rtcMem.command != CMDWAIT && rtcMem.command != CMDSEND) || rtcMem.hour == timeClient.getHours()) {
    // past the hour so we can send 
    rtcMem.command = CMDSEND;
  } else {
    rtcMem.command = CMDWAIT;
  }

  rtcMem.hour = timeClient.getHours() + 1;
  if (rtcMem.hour > 23) rtcMem.hour = 0;

  delayMinutes = ONEHOUR - timeClient.getMinutes();
}

void setup() {
  delay(5000);  
  readFromRTCMemory(); 

  startWiFi();
  getNTPTime();

  dht.begin();
  Serial.begin(115200);
}

void loop() {
  /* technically we dont need to use the loop function since we are sleeping/waking the ESP01
  however, it is useful to use the loop to keep allow for retry attempts on sending data before sleeping
  as the interval between sleep/wake cycles may be high, we need this to make sure we send information before sleeping.
  */
  if(rtcMem.command == CMDSEND) {
    rtcMem.command = CMDWAIT;

    #if DEBUG
      Serial.println();
      Serial.print("connecting to ");
      Serial.println(host);
    #endif
  
    // Use WiFiClient class to create TCP connections
    WiFiClient client;
    const int httpPort = 80;
    if (!client.connect(host, httpPort)) {
      #if DEBUG
        Serial.println("connection failed");
      #endif
      return;
    }

    //
    getSensorData(temperature, humidity);
  
    // We now create a URI for the request
    sprintf_P(url, PSTR("/post_data.php?a=%s&g=%i&t=%s&h=%s"), authorization_id, greenhouse_id, temperature, humidity);
    #if DEBUG
      Serial.print("Requesting URL: ");
      Serial.println(url);
    #endif
  
    // This will send the request to the server
    client.print(String("GET ") + url + " HTTP/1.0\r\n" + "Host: " + host + "\r\n" + "Connection: close\r\n\r\n");


    unsigned long timeout = millis();
    while (client.available() == 0) {
      if (millis() - timeout > TIMEOUT) {
        #if DEBUG
          Serial.println(">>> Client Timeout !");
        #endif
        client.stop();
        return;
      }
    }
  
    // Read all the lines of the reply from server and print them to Serial
    while(client.available()){
      String line = client.readStringUntil('\n');
      #if DEBUG
        Serial.println(line);
      #endif 
      if (line == "\r") {
        #if DEBUG
          Serial.println("Headers received");
        #endif
        break;
      }
    }

    String line = client.readStringUntil('\n');
    
    Serial.println("Reply was:");
    Serial.println("==========");
    Serial.println(line);
    Serial.println("==========");
    if (line.startsWith("001")) {
      Serial.println("Data stored successfully!");
    } else {
      Serial.println("Data store has failed");
      // return to the start of the loop and reattempt
      return;
    }

    Serial.println();
    #if DEBUG
      Serial.println("closing connection");
    #endif

  }

  writeToRTCMemory();

  // go to sleep to save energy
  #if DEBUG
    Serial.print("Sleeping for ");
    Serial.print(delayMinutes);
    Serial.println(" minutes");
    Serial.println("Night Night!");
  #endif
  ESP.deepSleepInstant(delayMinutes * ONEMINUTE);
}

