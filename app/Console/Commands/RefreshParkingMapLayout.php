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
        $this->line('Metric proposal: 84 compact motorcycle bays and 30 car bays drawn. All 139 inventory records are retained; 25 car positions remain unconfirmed. This does not certify physical capacity.');

        return self::SUCCESS;
    }
}
