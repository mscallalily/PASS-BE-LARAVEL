<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Removes everything created by SampleDataSeeder, including the five sample
 * accounts. Real records are never touched.
 */
class RemoveSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Sample data can only be removed in local/testing environments.');
        }

        $counts = SampleDataSeeder::removeSampleRecords(keepUsers: false);

        if (!$counts) {
            $this->command->info('No sample data found.');
            return;
        }

        $this->command->info('Sample test data removed:');
        foreach ($counts as $table => $count) {
            $this->command->line("  {$table}: {$count}");
        }
    }
}
