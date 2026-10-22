# PASS hardware: staged development and assembly

This package completes the PC-to-PASS software stage. ESP32 firmware, servo control and slot sensor ingestion are later stages; they are not implemented by this installer. No deployment is required for local testing.

## Stage 1 — USB RFID reader → PC → PASS

The reader window belongs to the parking staff's gate workstation. In PASS, parking staff and administrators can open it from RFID Access Records → Open USB reader. It is a separate local window on the same PC as the USB reader and its local service. It is not a regular-user or guest page. Assigning/revoking RFID credentials remains in the administrator's existing RFID Management flow.

The reader uses the PASS navy (#080677), sky (#96D1F2), light background and official logo. The gate console emphasizes the last confirmed result, vehicle plate, assigned/released slot and explicit Entry/Exit controls. It includes a Philippine clock, optional fullscreen and the latest 30 confirmed results from this window. Session counters reset on page reload and are not campus-wide or daily totals. The logo comes from the sibling `PASS-FE-REACT/public/branding/pass-logo.png`; it does not fetch assets or credentials from an external website.

A confirmed green result refers to a saved access record, not a physical barrier position. A new scan clears the previous vehicle while awaiting its response. Unknown tags cannot inherit the preceding vehicle's details. Already-inside and missing-exit results have distinct attention states. An unconfirmed response pauses capture and requires review of PASS records. Fullscreen and returning focus never silently resume scanning. The reader input remains active during processing so held tags are still suppressed.

Regular-user Dashboard now focuses on that account's current parking session and slot, selected vehicle/RFID status, today's own entries/exits, recent parking history, license requirements and private notifications. The campus map remains available under Parking Info. The dashboard API ignores caller-supplied user IDs, uses the owner recorded on each historical transaction, and uses Philippine calendar boundaries for today's activity. Reading it does not mark notifications as read. Loading, empty and connection-error states remain separate. Existing admin and parking staff dashboards are retained.

The reader is a USB keyboard device: one 24-character EPC followed by Enter. Plug it into the PC. Keep all other tags outside reading range. Use the locally served PASS reader window; opening another app or leaving the scan field pauses capture. Keep your Laravel backend on 127.0.0.1:8000 and your normal PASS frontend running separately.

1. Run `php artisan test --filter=PhysicalRfidReaderTest` against the existing isolated testing database. Stop if it fails; do not reset the normal database.
2. Run `node --test tools/rfid/scan-core.test.mjs`.
3. Preview reader provisioning: `php tools/rfid/Setup-PassUsbReader.php`.
4. Apply: `php tools/rfid/Setup-PassUsbReader.php --apply`.
5. In a separate backend terminal: `php -S 127.0.0.1:8787 -t tools/rfid tools/rfid/router.php`.
6. On that same PC open `http://127.0.0.1:8787/`. Keep the PHP terminal open.
7. Choose Entry, then Start scanning. Present the already assigned tag for an approved, selected vehicle with a current verified license. Expect `granted` with a slot in the appropriate CAR or MC lot.
8. Keep the tag in range: repeated reads should be ignored and only one active parking transaction should exist. Remove it from range afterward.
9. Pause, choose Exit, remove the tag for at least three seconds, then Start scanning and present it again. Expect `completed`; the slot becomes available. Do not use the simulator for this acceptance test.
10. Check PASS Access Records, occupancy and the assigned vehicle's parking history. The source should be reader; this scan is a real parking record, not a demo.

Assignment alone does not create a parking entry. An unknown tag should be denied without using a slot. Direction stays where the operator set it; repeated tag reads never switch Entry into Exit. Only one reader window should be active. A second window cannot take over an active reader lease; close the other window and wait 20 seconds if needed.

If the API response is lost or times out, scanning pauses. Check the actual PASS records first: a scan may already have saved before the response was lost. After confirming the state, tick the confirmation box and resume. There are no automatic mutation retries. Never manually delete a transaction to reconcile a test.

The reader device key is generated locally and stored under the backend's `.git/pass-usb-rfid/reader.json`, protected for the current OS user. It is not sent to browser JavaScript or printed. Keep it private; do not upload this directory or paste its contents. Setup uses the local MySQL database and refuses a remote/testing database from its CLI. It retains an existing matching reader key. It does not reassign tags, seed parking slots or alter user accounts. An existing reader with unknown credentials is not overwritten.

This local PHP server is a development bench tool. Keep its loopback binding, do not expose port 8787 to the internet, and do not host this tool as a public Railway/Vercel page. Its Host and Origin checks expect exactly `127.0.0.1:8787`.

## Stage 2 — Identify the exact boards, then ESP32 upload

Before pin-by-pin instructions, send clear front/back photos of each ESP32 board (all pin labels and USB chip visible), HC-SR04 markings, the servo label/three-wire plug, and the 5V supply output label and connector. DevKit V1 clones can have different header layouts; the WROOM metal shield name alone does not identify the board's power wiring.

Label boards A/B. Test each board separately with a USB data cable and a serial heartbeat sketch. Confirm COM port, successful upload and stable output before attaching any sensor or servo. We will supply and test the actual firmware in this stage. Do not permanently mount the model yet.

## Stage 3 — Servo bench test

Identify whether the SG90 is a positional or continuous-rotation variant before selecting control code. Test it without a barrier arm first, using its specified supply and a common signal ground. Do not power the servo from the ESP32 3V3 pin. Determine the board's safe USB/external power arrangement from its actual markings before joining power rails. Add a light arm only after neutral/closed/open positions are calibrated without forcing the end stops.

Next, connect a confirmed PASS result to an ESP32 command with an acknowledgement and bounded lifetime. A stale command, duplicate scan, unknown tag or lost API response must never trigger a new opening. Barrier firmware and that command channel are not included yet. Supervised toy-model testing comes before any vehicle-scale gate use.

## Stage 4 — One slot sensor and two LEDs

Bench-test one HC-SR04 and one red/green LED pair. The standard HC-SR04 uses a 5V supply. ESP32 uses 3.3V logic; a standard 5V Echo must not connect directly to its GPIO. For the identified standard module, the planned Echo divider is 1kΩ from Echo to the GPIO junction, then 2kΩ from the junction to GND: nominal 5V × 2/(1+2) = 3.33V. Each Echo line needs its own divider. Check actual module variant, supply voltage, resistor values and breadboard connections before powering it.

Each LED needs its own series resistor. We will choose the GPIOs after reading the board labels, then provide the wiring table, sketch and a close-up connection diagram. Calibrate empty/occupied readings using the real model and toys; do not use an arbitrary distance threshold. An invalid/absent sensor reading must show unknown, not falsely available. Require stable samples so a hand or brief reflection does not flicker the slot status.

## Stage 5 — All six sensors and twelve LEDs

Expand only after one slot passes. Give each sensor/LED pair a physical label and a unique PASS slot mapping. A possible model split is three CAR bays and three MC bays; choose the final mappings before coding them. Sample ultrasonic sensors in sequence to reduce interference. Test each slot separately and then together.

Six sensors directly monitor six physical model bays. They do not measure every bay in the full campus layout. Retain the distinction between sensor observation and parking entries/exits; do not add both as separate occupied vehicles or change campus capacity to six without an explicit decision. Unexpected occupation, unknown readings and stale device heartbeats need separate states.

## Stage 6 — End-to-end model acceptance

Test authorized entry, unknown/revoked tag, an unselected second vehicle, duplicate reads, full lot, exit, restart, lost Wi-Fi/API, sensor obstruction and recovery. Check that event history, active transactions, held slots and observed slot status remain consistent. Fit the model only after the tests pass; label wires and leave access to power and USB connections.

With all six sensors allocated to bays, none is dedicated to detecting an obstruction under the barrier. The final gate-closing process needs a separate presence/obstruction plan; a blind timer is not sufficient for an unattended real gate.

## References checked on 22 October 2026

- Espressif, ESP32-WROOM-32 datasheet: https://documentation.espressif.com/esp32-wroom-32_datasheet_en.html — module voltage and I/O electrical characteristics. A development board's input supply is a separate board-level question.
- SparkFun, standard HC-SR04: https://www.sparkfun.com/ultrasonic-distance-sensor-hc-sr04.html — 5V supply and VCC/Trig/Echo/GND signals. Confirm the actual module variant.
- TowerPro, SG90 Analog: https://towerpro.com.tw/product/sg90-analog/ — external supply and model specifications. Confirm the actual servo variant before powering it.
- PHP built-in web server: https://www.php.net/manual/en/features.commandline.webserver.php — local development server and router usage.

## Source verification before delivery

The package includes physical-reader API tests and HID scan guard tests. Additional browser tests exercised the real PHP bridge against a controlled API stub, including repeat suppression, focus loss, 320/390/768/1440 layouts, secret isolation, blocked foreign Origin/Host, offline errors and a timed-out request. This does not substitute for the owner's real USB scan and Windows assembly acceptance tests.

New intentional source to include in the next reviewed commit: `tools/rfid/`, `tests/Feature/Parking/PhysicalRfidReaderTest.php`, and `app/Http/Controllers/Api/RfidReaderController.php`. Keep unrelated old untracked tools, backups and SQL dumps out of that commit.
