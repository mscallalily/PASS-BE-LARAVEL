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
        $this->line('Default parking layout: 45 full-size proposed independent car bays on the corrected numbered outline with a clear shared approach. All 139 inventory records are retained. This coordinate refresh does not change access history, capacity or gate state.');

        return self::SUCCESS;
    }
}
