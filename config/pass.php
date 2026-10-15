<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Development gate simulator
    |--------------------------------------------------------------------------
    |
    | Stands in for the RFID reader and QR scanner while no hardware is
    | connected. Scans go through the real ParkingAccessService, so they are
    | saved like any gate event. It is available only when enabled here, never
    | when APP_ENV=production, and only to the listed tester accounts
    | (comma-separated user codes) who are also Parking Staff.
    |
    */

    'simulator' => [
        'enabled' => (bool) env('PASS_SIMULATOR_ENABLED', false),
        'testers' => array_values(array_filter(array_map('trim', explode(',', (string) env('PASS_SIMULATOR_TESTERS', ''))))),
    ],

];
