#include <NTPClient.h>
#include <ESP8266WiFi.h>
#include <WiFiUdp.h>

#include "DHT.h"

#define DEBUG 1

#define DHTPIN 2        // what digital pin we're connected to
#define DHTTYPE DHT22   // DHT 22
#define ONEMINUTE 6e7   // 1 minute (in microseconds)
#define ONEHOUR 60      // 60 minutes in an hour
#define TIMEOUT 20000   // wait 20 seconds for timeout to website
#define MAXRETRIES 30
#define CMDWAIT 100
#define CMDSEND 101
#define MIDNIGHT 24
#define RTCMEMORYSTART 64
#define HOURS 0
#define COMMAND 1

#if DEBUG == 1
#define debug(x) Serial.print(x)
#define debugln(x) Serial.println(x)
#else
#define debug(x)
#define debugln(x)
#endif


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

const char* ssid = "***REMOVED***";
const char* password = "***REMOVED***";
const char* host = "***REMOVED***";
const char* NTPaddress = "europe.pool.ntp.org";

char url[52];
char temperature[10];
char humidity[10];
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
  dtostrf(t,1,2,temperature);
  dtostrf(h,1,2,humidity);
}

void readFromRTCMemory() {
  debugln("-----readFromRTCMemory-----");

  if (ESP.rtcUserMemoryRead(0, (uint32_t *)&rtcData, sizeof(rtcData))) {
    debugln("Reading memory : ");

    uint32_t crcOfData = calculateCRC32((uint8_t *)&rtcData.data[0], sizeof(rtcData.data));

    if (crcOfData != rtcData.crc32) {
      debugln("CRC32 memory does not match CRC32 of data. Invalid!");
      
      dataValid = false;
    } else {
      debugln("CRC32 check ok, data is valid.");
      debug("hour: ");
      debugln(rtcData.data[HOURS]);
      debug("cmd: ");
      debugln(rtcData.data[COMMAND]);
      dataValid = true;
    }
  }
}

void writeToRTCMemory() {
  debugln("-----writeToRTCMemory-----");
  const uint32_t memoryOffset = 0;
  
  // Update CRC32 of data
  rtcData.crc32 = calculateCRC32((uint8_t *)&rtcData.data[0], sizeof(rtcData.data));
  // Write struct to RTC memory
  if (ESP.rtcUserMemoryWrite(memoryOffset, (uint32_t *)&rtcData, sizeof(rtcData))) {
    debugln("Write Data: ");
    debug("hour: ");
    debugln(rtcData.data[HOURS]);
    debug("cmd: ");
    debugln(rtcData.data[COMMAND]);
  }
}

void startWiFi(){
  debugln("-----startWiFi-----");


  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  //int count = 0;
  //while (WiFi.status() != WL_CONNECTED && count <= MAXRETRIES) {
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);

    debug(".");

    //count ++;
  }

  // put the ESP to sleep after too many unsuccessful retries to conserve battery
  //if (count == MAXRETRIES || WiFi.localIP().toString()=="0.0.0.0") ESP.deepSleepInstant(ONEMINUTE);
  if (WiFi.localIP().toString()=="0.0.0.0") ESP.deepSleepInstant(ONEMINUTE);

  debugln("");
  debug("Connected! IP address: ");
  debugln(WiFi.localIP());

}


TimeValue getNTPTime(){
  debugln("-----getNTPTime-----");
  const uint32_t offestFromGMT = 0;

  WiFiUDP ntpUDP;
  NTPClient timeClient(ntpUDP, NTPaddress, offestFromGMT);
  timeClient.begin();
  while (!timeClient.isTimeSet()){
    timeClient.update();
    delay(1000);
  }


  debug("Current time is: ");
  debugln(timeClient.getFormattedTime());

  return {timeClient.getHours(), timeClient.getMinutes()};
}

void setNextWakeTime(){
  debugln("-----setNextWakeTime-----");

  TimeValue time = getNTPTime();

  readFromRTCMemory(); 

  if (dataValid) {
    debug("Time Hours:");
    debugln(time.hour);
    debug("RTC Hours:");
    debugln(rtcData.data[HOURS]);
    
    if(rtcData.data[HOURS] == time.hour) { 
      debugln("hours match, sending");
      rtcData.data[COMMAND] = CMDSEND;
    }    
  } else {
    // data stored isnt valid so must be a power on rather than deep sleep
    debugln("invalid data (probably fresh boot), sending");
    rtcData.data[COMMAND] = CMDSEND;
  }

  if(rtcData.data[COMMAND] == CMDSEND){
    rtcData.data[HOURS] = time.hour + 1;
    if (rtcData.data[HOURS] > 23) rtcData.data[HOURS] = 0;
  }

  delayMinutes = ONEHOUR - time.minutes;

  debug("hour: ");
  debugln(rtcData.data[HOURS]);
  debug("command: ");
  debugln(rtcData.data[COMMAND]);

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

    debugln();
    debug("connecting to ");
    debugln(host);
  
    // Use WiFiClient class to create TCP connections
    WiFiClient client;
    const int httpPort = 80;
    if (!client.connect(host, httpPort)) {
      debugln("connection failed");
      return;
    }

    //
    getSensorData(temperature, humidity);
  
    // We now create a URI for the request
    sprintf_P(url, PSTR("/api.php?c=save&a=%s&g=%i&t=%s&h=%s"), authorization_id, greenhouse_id, temperature, humidity);
    debug("Requesting URL: ");
    debugln(url);

  
    // This will send the request to the server
    client.print(String("GET ") + url + " HTTP/1.0\r\n" + "Host: " + host + "\r\n" + "Connection: close\r\n\r\n");


    unsigned long timeout = millis();
    while (client.available() == 0) {
      if (millis() - timeout > TIMEOUT) {
        debugln(">>> Client Timeout !");

        client.stop();
        return;
      }
    }
  
    // Read all the lines of the reply from server and print them to Serial
    while(client.available()){
      String line = client.readStringUntil('\n');
      debugln(line);
      if (line == "\r") {
        debugln("Headers received");
        break;
      }
    }

    String line = client.readStringUntil('\n');
    
    debugln("Reply was:");
    debugln("==========");
    debugln(line);
    debugln("==========");

    if (line.indexOf("OK") > 0) {
      debugln("Data stored successfully!");
    } else {
      debugln("Data store has failed");
      // return to the start of the loop and reattempt
      return;
    }

    debugln();

    debugln("closing connection");
  }

  writeToRTCMemory();

  // go to sleep to save energy

  debug("Sleeping for ");
  debug(delayMinutes);
  debugln(" minutes");
  debugln("Night Night!");

  ESP.deepSleepInstant(delayMinutes * ONEMINUTE);
}

