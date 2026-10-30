/*
  PASS slot guidance + ONE FIXED slot sensor. No servo or buck is needed.
  ESP32 Arduino core 3.x. Libraries: Adafruit GFX, Adafruit SSD1306.
  Existing wiring: HC-SR04 TRIG D18; ECHO D19 via voltage divider;
  green LED D23; red LED D22; OLED SDA D25, SCK/SCL D26, VDD 3V3.
  Keep the existing sensor supply and shared ESP32 ground.
  Upload on COM10; then CLOSE Serial Monitor before starting Start-PassParking.
  Staff bind the sensor's physical slot in the dashboard. The sensor stays
  at that slot; the gate OLED can display assignments for other slots too.
  This sketch cannot identify a vehicle and never opens a barrier.
*/
#include <Arduino.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <esp_random.h>
#include <cstring>

constexpr int TRIG_PIN=18, ECHO_PIN=19, GREEN_LED=23, RED_LED=22;
Adafruit_SSD1306 display(128,64,&Wire,-1);
bool oledReady=false;
byte oledAddress=0;
char bootId[9], frame[224];
size_t frameLength=0;
bool frameOverflow=false;
char fixedSlot[31]="NOT CONFIGURED", fixedArea[41]="", fixedZone[31]="";
char plate[21]="", assignedArea[41]="", assignedZone[31]="", assignedSlot[31]="";
enum class Screen { Welcome, Assignment, SlotStatus };
Screen screen=Screen::Welcome;
constexpr uint32_t ASSIGNMENT_MS=20000, SLOT_STATUS_MS=5000, SENSOR_SETTLE_MS=2000;
uint32_t screenAt=0, lastDraw=0, lastProbe=0, candidateAt=0;
const char* candidateState="unknown";
const char* settledState="unknown";
bool sensorBaseline=false;
const char* sensorState="unknown";
float distanceCm=0;
bool distanceValid=false;

void copyText(char* target, size_t capacity, const char* value){
  strncpy(target,value,capacity-1);target[capacity-1]='\0';
}

void probeOLED(){
  bool previous=oledReady;
  if(oledAddress){Wire.beginTransmission(oledAddress);if(Wire.endTransmission()!=0){oledReady=false;oledAddress=0;}}
  if(!oledAddress){
    for(byte candidate=0x3C;candidate<=0x3D;candidate++){
      Wire.beginTransmission(candidate);
      if(Wire.endTransmission()==0){oledAddress=candidate;break;}
    }
    if(oledAddress)oledReady=display.begin(SSD1306_SWITCHCAPVCC,oledAddress,true,false);
  }
  if(oledAddress&&!oledReady)oledReady=display.begin(SSD1306_SWITCHCAPVCC,oledAddress,true,false);
  if(previous!=oledReady){Serial.print("PASS1|HEALTH|");Serial.println(oledReady?"OLED_OK":"OLED_OFFLINE");}
}

void hello(){Serial.print("PASS1|HELLO|");Serial.print(oledReady?"OLED_OK":"OLED_OFFLINE");Serial.print('|');Serial.println(bootId);}

void lineText(int y,const char* text){
  char clipped[22];copyText(clipped,sizeof(clipped),text);
  display.setTextSize(1);display.setCursor(0,y);display.print(clipped);
}

void scrollingText(int y,const char* text){
  const size_t length=strlen(text);
  if(length<=21){lineText(y,text);return;}
  size_t step=(millis()/500)%(length-21+8);
  size_t offset=step<4?0:step-4;
  if(offset>length-21)offset=length-21;
  lineText(y,text+offset);
}

void slotText(const char* slot){
  if(strlen(slot)<=10){display.setTextSize(2);display.setCursor(0,22);display.print(slot);}
  else{lineText(22,slot);if(strlen(slot)>21)lineText(31,slot+21);}
}

void showSlotStatus(){screen=Screen::SlotStatus;screenAt=millis();}

void updateSensorNotice(){
  if(strcmp(sensorState,candidateState)!=0){candidateState=sensorState;candidateAt=millis();}
  if(static_cast<uint32_t>(millis()-candidateAt)<SENSOR_SETTLE_MS)return;
  if(!sensorBaseline){settledState=candidateState;sensorBaseline=true;return;}
  if(strcmp(candidateState,settledState)!=0){
    settledState=candidateState;
    if(screen!=Screen::Assignment && strcmp(fixedSlot,"NOT CONFIGURED")!=0)showSlotStatus();
  }
}

void renderScreen(){
  if(screen==Screen::Assignment && static_cast<uint32_t>(millis()-screenAt)>=ASSIGNMENT_MS)showSlotStatus();
  if(screen==Screen::SlotStatus && static_cast<uint32_t>(millis()-screenAt)>=SLOT_STATUS_MS)screen=Screen::Welcome;
  if(!oledReady)return;
  display.clearDisplay();display.setTextColor(SSD1306_WHITE);display.setTextWrap(false);
  if(screen==Screen::Assignment){
    lineText(0,"ASSIGNED PARKING");lineText(11,plate);slotText(assignedSlot);
    scrollingText(43,assignedArea);scrollingText(54,assignedZone);
  }else if(screen==Screen::SlotStatus){
    lineText(0,"MONITORED SLOT");slotText(fixedSlot);
    lineText(43,strcmp(sensorState,"occupied")==0?"SLOT OCCUPIED":strcmp(sensorState,"available")==0?"SLOT CLEAR":"READING UNCERTAIN");
    display.setTextSize(1);display.setCursor(0,55);display.print("Distance: ");
    if(distanceValid){display.print(distanceCm,1);display.print(" cm");}else display.print("--");
  }else{
    display.setTextSize(2);display.setCursor(4,4);display.print("WELCOME TO");
    display.setCursor(40,25);display.print("PASS");
    lineText(47,"TAP RFID AT READER");lineText(56,"Parking guidance");
  }
  display.display();lastDraw=millis();
}

void handleFrame(){
  frame[frameLength]='\0';
  if(strcmp(frame,"?")==0){hello();return;}
  char* fields[7];size_t count=1;fields[0]=frame;
  for(char* p=frame;*p;p++)if(*p=='|'){
    if(count>=7)return;
    *p='\0';fields[count++]=p+1;
  }
  if(count!=7||strcmp(fields[0],"@PASS1")!=0||strlen(fields[1])!=16)return;
  for(const char* p=fields[1];*p;p++)if(!((*p>='0'&&*p<='9')||(*p>='a'&&*p<='f')))return;
  const bool bind=strcmp(fields[2],"BIND")==0,show=strcmp(fields[2],"DISPLAY")==0,clear=strcmp(fields[2],"CLEAR")==0;
  if(!bind&&!show&&!clear)return;
  if((bind||show)&&(!*fields[6]||strlen(fields[3])>20||strlen(fields[4])>40||strlen(fields[5])>30||strlen(fields[6])>30))return;
  if(bind){copyText(fixedSlot,sizeof(fixedSlot),fields[6]);copyText(fixedArea,sizeof(fixedArea),fields[4]);copyText(fixedZone,sizeof(fixedZone),fields[5]);screen=Screen::Welcome;sensorBaseline=false;candidateState=sensorState;candidateAt=millis();}
  if(show){copyText(plate,sizeof(plate),fields[3]);copyText(assignedArea,sizeof(assignedArea),fields[4]);copyText(assignedZone,sizeof(assignedZone),fields[5]);copyText(assignedSlot,sizeof(assignedSlot),fields[6]);screen=Screen::Assignment;screenAt=millis();}
  if(clear)screen=Screen::Welcome;
  probeOLED();renderScreen();
  Serial.print(oledReady?"PASS1|ACK|":"PASS1|ERROR|");Serial.print(fields[1]);
  if(!oledReady)Serial.print("|OLED_UNAVAILABLE");
  Serial.println();
}

void readFrames(){
  while(Serial.available()){
    const char c=static_cast<char>(Serial.read());
    if(c=='\r')continue;
    if(c=='\n'){
      if(!frameOverflow&&frameLength)handleFrame();
      frameLength=0;frameOverflow=false;
    }else if(c>=32&&c<=126&&!frameOverflow){
      if(frameLength<sizeof(frame)-1)frame[frameLength++]=c;
      else frameOverflow=true;
    }else if(c<32||c>126)frameOverflow=true;
  }
}

void setup(){
  Serial.begin(115200);
  snprintf(bootId,sizeof(bootId),"%08lx",static_cast<unsigned long>(esp_random()));
  pinMode(TRIG_PIN,OUTPUT);pinMode(ECHO_PIN,INPUT);pinMode(GREEN_LED,OUTPUT);pinMode(RED_LED,OUTPUT);
  digitalWrite(TRIG_PIN,LOW);digitalWrite(GREEN_LED,LOW);digitalWrite(RED_LED,LOW);
  Wire.begin(25,26);probeOLED();hello();renderScreen();
}

void loop(){
  readFrames();
  digitalWrite(TRIG_PIN,LOW);delayMicroseconds(2);digitalWrite(TRIG_PIN,HIGH);delayMicroseconds(10);digitalWrite(TRIG_PIN,LOW);
  const unsigned long duration=pulseIn(ECHO_PIN,HIGH,30000);
  distanceCm=duration*0.0343f/2.0f;distanceValid=duration>0&&distanceCm>=2&&distanceCm<=400;
  sensorState=!distanceValid?"unknown":distanceCm<=15?"occupied":distanceCm>=20?"available":"unknown";
  updateSensorNotice();
  digitalWrite(RED_LED,strcmp(sensorState,"occupied")==0?HIGH:LOW);
  digitalWrite(GREEN_LED,strcmp(sensorState,"available")==0?HIGH:LOW);
  Serial.print("PASS1|SENSOR|");Serial.print(sensorState);Serial.print('|');
  if(distanceValid)Serial.println(distanceCm,1);else Serial.println("--");
  if(millis()-lastProbe>=2000){probeOLED();lastProbe=millis();}
  if(millis()-lastDraw>=300)renderScreen();
  delay(150);
}
