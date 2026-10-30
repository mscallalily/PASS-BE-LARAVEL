# PASS OLED guidance and fixed-slot sensor

This replaces the temporary servo test/without-servo sketch with a slot-guidance station. The faulty servo and its buck module stay disconnected.

## Setup on the gate laptop

1. Stop the old hardware PowerShell controller with Ctrl+C and pause scanning during setup. Run the installer from PASS-BE-LARAVEL. It backs up source files and migrates only the new station table/arrival timestamp.
2. Open tools/hardware/PASS_SlotStation/PASS_SlotStation.ino in Arduino IDE. Use the existing ESP32 board, COM10 and the installed Adafruit GFX/SSD1306 libraries. Upload. Close Serial Monitor afterwards.
3. Sign in as Parking Staff. In the new Parking guidance & slot sensor panel, select USB-MAIN-GATE-01 and the exact physical slot where the sensor is installed. Save. Administrators can monitor but cannot change operations configuration.
4. Start node .\tools\hardware\Start-PassParking.cjs --port COM10 from PASS-BE-LARAVEL. Keep this window open. It shares the existing private reader configuration; do not post reader.json or its key in chat.
5. Focus the USB Reader browser page at http://127.0.0.1:8787 and use the correct Entry/Exit direction. Keep the existing PHP reader server, Laravel API and frontend running. The Node parking station owns ESP32 USB and port 8788. Do not run Start-PassHardware.cjs at the same time.

## Behavior

- The idle OLED reads WELCOME TO PASS and TAP RFID AT READER. A new, approved entry assigns a slot using PASS's existing vehicle/authorization/license rules. Its committed plate, area, zone and slot appear for 20 seconds, followed by the MONITORED SLOT screen for 5 seconds, then Welcome again.
- MONITORED SLOT always names the fixed physical sensor slot (for example M-04). It never pretends a reading belongs to a different slot shown in the driver's assignment. A settled change in the sensor state also shows this status briefly, then returns to Welcome. Initial baseline readings do not interrupt Welcome; a new assignment has priority over sensor notices.
- The bridge retains a fresh display instruction during a brief OLED outage or missing acknowledgement and retries for up to 30 seconds after receipt. A newer instruction replaces it; an exit/denied event cancels it. Repeated polls or already acknowledged events do not replay it. USB/session restarts still start from a fresh event cursor.
- Another vehicle's OLED instruction NEVER moves the sensor binding. One ultrasonic sensor covers one fixed slot only. Staff must change the binding and restart the station if it is physically moved.
- Fixed-slot readings settle for roughly two seconds before a state change is sent. <=15 cm is occupied, >=20 cm is clear; invalid readings are uncertain. These are the prototype thresholds, not a campus installation calibration.
- When a fresh occupied transition follows a clear baseline near or after the active assignment, staff receive an arrival alert associated with that assigned slot. The dashboard updates within about three seconds.
- Ultrasonic detection cannot identify the occupant. The alert says which vehicle is assigned, and explicitly leaves identity verification to staff. Existing staff-confirmed fields and wrong-slot reports are retained.
- Startup-occupied, invalid, stale and disconnected readings do not confirm an arrival. Repeated observations do not repeat the alert. A clear sensor does not close a parking stay or reassign its slot: gate exit remains the normal exit workflow.
- Slots with configured sensors that are offline, uncertain or occupied are excluded from new automatic allocations. Unmonitored slots retain their existing allocation rules. Assignment/inside counts continue to reflect recorded stays; the hardware panel separately reports physical sensor state.
- Duplicate Already Inside scans create no new entry and no new display event. Denied/exit events clear old instructions. Development simulator events do not drive the physical display.
- Existing automatic-barrier integration, when installed, is suppressed at gates with this servo-less station. No OPEN is queued there. Visitor QR and authorized emergency actions remain recorded using the existing checks.
- Business actions do not require attached hardware or a browser Connect Hardware step. If the configured API is Railway/another remote server, deploy the backend additions and run the new migration on that API before starting the station. Running the installer locally does not deploy a remote server.

## Wiring retained

HC-SR04 TRIG D18; ECHO D19 through the existing voltage divider. Green LED D23; red LED D22. OLED SDA D25, SCK/SCL D26, VDD 3V3, shared GND. The OLED is a gate guide while the ultrasonic sensor must face its configured parking bay. USB wire length and physical placement limit this single-ESP32 prototype; an actual distant gate/bay installation needs a separately networked bay sensor.

## Test

Clear the fixed sensor slot and wait until the staff panel says Slot clear. Make a genuine approved new RFID entry; check the assigned location on the OLED. If it matches the monitored slot, put a model vehicle/object 10 cm from the sensor and hold it still. Watch for the staff arrival alert. If the assigned slot is elsewhere, do not claim the monitored slot detects that vehicle. Removing the object should show Slot clear but keep the stay active until its normal gate exit.

## Diagnostics

- http://127.0.0.1:8788/status reports parking-station mode, USB session, OLED readiness, event cursor and pending/last acknowledged guidance IDs. Acknowledgement indicates the firmware handled the frame, not a measurement of visible pixels or physical barrier movement.
- Upload PASS_SlotStation.ino if the controller reports a missing protocol handshake. It intentionally sends no structured command to the previous O/C/M firmware.
- USB reconnects automatically on the configured port. A changed COM number needs --port with the new value. API authentication, gate/configuration errors stop the station visibly rather than pretending it is ready.
- Staff panel offline status appears after 12 seconds without an accepted observation. A stale packet or old station session cannot refresh it.
- Backend feature tests: php artisan test --filter=ParkingStationTest (separate seeded *_testing database only). Bridge checks: node --test tools/hardware/parking-station.test.cjs.
- Physical ESP32/OLED/sensor operation still needs the on-device upload/test. Host validation cannot verify wiring, supply voltage, measured distance, or display visibility.
