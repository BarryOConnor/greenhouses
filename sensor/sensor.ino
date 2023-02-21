#include <NTPClient.h>
#include <ESP8266WiFi.h>
#include <WiFiUdp.h>
#include "DHT.h"

#define DHTPIN 2        // what digital pin we're connected to
#define DHTTYPE DHT22   // DHT 22
#define ONEMINUTE 6e7   // 1 minute (in microseconds)
#define ONEHOUR 60      // 60 minutes in an hour
#define TIMEOUT 20000   // wait 20 seconds for timeout to website
#define DEBUG true      // display debug messages
#define MAXRETRIES 30
#define CMDWAIT 100
#define CMDSEND 101
#define MIDNIGHT 24
#define RTCMEMORYSTART 64
#define HOURS 0
#define COMMAND 1


// data[0] will be the hours, data[1] will be the command
typedef struct {
  uint32_t crc32;
  byte data[2];
} RTCStore;

typedef struct {
  int hour = -1;
  int minutes = -1;
} TimeValue;

RTCStore rtcData;

const char* ssid = ***REMOVED***;
const char* password = ***REMOVED***;
const char* host = ***REMOVED***;
char url[46];
char temperature[7];
char humidity[7];
const char authorization_id[9] = "***REMOVED***";
const int greenhouse_id = 1;
double delayMinutes = 0;
bool dataValid = false;

DHT dht(DHTPIN, DHTTYPE);

uint32_t calculateCRC32(const uint8_t *data, size_t length) {
  uint32_t crc = 0xffffffff;
  while (length--) {
    uint8_t c = *data++;
    for (uint32_t i = 0x80; i > 0; i >>= 1) {
      bool bit = crc & 0x80000000;
      if (c & i) { bit = !bit; }
      crc <<= 1;
      if (bit) { crc ^= 0x04c11db7; }
    }
  }
  return crc;
}

void getSensorData(char *temp, char *humidity){
  // collect data
  float h = dht.readHumidity();
  float t = dht.readTemperature();
  dtostrf(t,5,2,temperature);
  dtostrf(h,5,2,humidity);
}

void readFromRTCMemory() {
  #if DEBUG
    Serial.println("-----readFromRTCMemory-----");
  #endif

  if (ESP.rtcUserMemoryRead(0, (uint32_t *)&rtcData, sizeof(rtcData))) {
    #if DEBUG
      Serial.println("Reading memory : ");
    #endif

    uint32_t crcOfData = calculateCRC32((uint8_t *)&rtcData.data[0], sizeof(rtcData.data));

    if (crcOfData != rtcData.crc32) {
      #if DEBUG
        Serial.println("CRC32 memory does not match CRC32 of data. Invalid!");
      #endif
      dataValid = false;
    } else {
      #if DEBUG
        Serial.println("CRC32 check ok, data is valid.");
        Serial.print("hour: ");
        Serial.println(rtcData.data[HOURS]);
        Serial.print("cmd: ");
        Serial.println(rtcData.data[COMMAND]);
      #endif
      dataValid = false;
    }
  }
}

void writeToRTCMemory() {
  #if DEBUG
  Serial.println("-----writeToRTCMemory-----");
  #endif
  
  // Update CRC32 of data
  rtcData.crc32 = calculateCRC32((uint8_t *)&rtcData.data[0], sizeof(rtcData.data));
  // Write struct to RTC memory
  if (ESP.rtcUserMemoryWrite(0, (uint32_t *)&rtcData, sizeof(rtcData))) {
    #if DEBUG
      Serial.println("Write Data: ");
      Serial.print("hour: ");
      Serial.println(rtcData.data[HOURS]);
      Serial.print("cmd: ");
      Serial.println(rtcData.data[COMMAND]);
    #endif
  }
}

void startWiFi(){
  #if DEBUG
    Serial.println("-----startWiFi-----");
  #endif

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
  if (count == MAXRETRIES || WiFi.localIP().toString()=="0.0.0.0") ESP.deepSleepInstant(ONEHOUR * ONEMINUTE);

  #if DEBUG
    Serial.println("");
    Serial.print("Connected! IP address: ");
    Serial.println(WiFi.localIP());
  #endif
}


TimeValue getNTPTime(){
  #if DEBUG
    Serial.println("-----getNTPTime-----");
  #endif

  WiFiUDP ntpUDP;
  NTPClient timeClient(ntpUDP, "europe.pool.ntp.org", 0);
  timeClient.begin();
  while (!timeClient.isTimeSet()){
    timeClient.update();
    delay(1000);
  }

  #if DEBUG
    Serial.print("Current time is: ");
    Serial.println(timeClient.getFormattedTime());
  #endif
  return {timeClient.getHours(), timeClient.getMinutes()};
}

void setNextWakeTime(){
  #if DEBUG
  Serial.println("-----setNextWakeTime-----");
  #endif

  TimeValue time = getNTPTime();

  readFromRTCMemory(); 

  if (!dataValid) {
    // data stored isnt valid so must be a power on rather than deep sleep
    rtcData.data[COMMAND] = CMDSEND;
  } else {
    if(rtcData.data[HOURS] == time.hour) { 
      rtcData.data[COMMAND] = CMDWAIT;
    } else {
      rtcData.data[COMMAND] = CMDSEND;
    }
  }

  if(rtcData.data[COMMAND] == CMDSEND){
    rtcData.data[HOURS] = time.hour + 1;
    if (rtcData.data[HOURS] > 23) rtcData.data[HOURS] = 0;
  }

  delayMinutes = ONEHOUR - time.minutes;

  #if DEBUG
    Serial.print("hour: ");
    Serial.println(rtcData.data[HOURS]);
    Serial.print("command: ");
    Serial.println(rtcData.data[COMMAND]);
  #endif  

  
}

void setup() {
  Serial.begin(115200);
  
  startWiFi();
  setNextWakeTime();
  
  dht.begin();  
}

void loop() {
  /* technically we dont need to use the loop function since we are sleeping/waking the ESP01
  however, it is useful to use the loop to keep allow for retry attempts on sending data before sleeping
  as the interval between sleep/wake cycles may be high, we need this to make sure we send information before sleeping.
  */
  if(rtcData.data[COMMAND] == CMDSEND) {
    rtcData.data[COMMAND] = CMDWAIT;

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

