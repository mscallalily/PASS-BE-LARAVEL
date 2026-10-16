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
        $this->line('M-23–M-42 are proposed west-bank positions. Boundary dimensions remain unverified source-image labels.');

        return self::SUCCESS;
    }
}
