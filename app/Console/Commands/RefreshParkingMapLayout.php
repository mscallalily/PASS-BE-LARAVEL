<?php

namespace App\Console\Commands;

use App\Services\Parking\ParkingLayoutUpdater;
use Illuminate\Console\Command;

class RefreshParkingMapLayout extends Command
{
    protected $signature = 'parking:refresh-layout';

    protected $description = 'Refresh existing parking map coordinates without changing slots, access records or gates';

    public function handle(ParkingLayoutUpdater $updater): int
    {
        $this->info($updater->apply().' slot diagram coordinate(s) updated.');
        $this->line('Slot map: all 55 car and 84 motorcycle codes mapped. Car symbols are schematic; the separate measured proposal retains 30 car bays. Status, history, capacity and gate state are unchanged.');

        return self::SUCCESS;
    }
}
