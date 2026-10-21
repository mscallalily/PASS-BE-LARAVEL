<?php

namespace Tests\Feature\Parking;

use Database\Seeders\ProductionReferenceSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ProductionReadinessTest extends ParkingTestCase
{
    private function productionConfig(): void
    {
        config([
            'app.env' => 'production', 'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.url' => 'https://api.pass.test', 'app.frontend_url' => 'https://pass.test',
            'database.default' => 'mysql', 'pass.simulator.enabled' => false,
            'mail.default' => 'resend', 'services.resend.key' => 'SYNTHETIC-PRIVATE-KEY',
            'mail.from.address' => 'pass@pass.test', 'cache.default' => 'file',
            'queue.default' => 'sync', 'pass.deployment.volume_mount_path' => storage_path(),
            // Process calls are faked; do not inherit the owner's Windows OCR paths.
            'services.license_ocr.tesseract_path' => 'tesseract',
            'services.license_ocr.pdftotext_path' => 'pdftotext',
        ]);
        Process::fake(['*' => Process::result(output: 'Available', exitCode: 0)]);
        Storage::fake('private');
    }

    public function test_production_check_validates_without_sending_mail_or_leaving_storage_files(): void
    {
        $this->productionConfig();
        $before = DB::table('users')->count();
        $status = Artisan::call('pass:check-production', ['--railway' => true]);
        $output = Artisan::output();
        $this->assertSame(0, $status, $output);
        $this->assertStringNotContainsString('SYNTHETIC-PRIVATE-KEY', $output);
        $this->assertStringContainsString('real inbox delivery still need verification', $output);
        $this->assertSame([], Storage::disk('private')->allFiles());
        $this->assertSame($before, DB::table('users')->count());
        Process::assertRan(fn ($process) => $process->command === ['tesseract', '--version']);
        Process::assertRan(fn ($process) => $process->command === ['pdftotext', '-v']);
    }

    public function test_unsafe_origins_debug_simulator_and_missing_runtime_dependencies_fail_closed(): void
    {
        $this->productionConfig();
        config(['app.debug' => true, 'pass.simulator.enabled' => true]);
        Process::fake(['*' => Process::result(exitCode: 1)]);
        foreach (['http://pass.test', 'https://localhost', 'https://user@pass.test', 'https://pass.test/subpath', 'https://pass.test?token=SECRET'] as $url) {
            config(['app.frontend_url' => $url]);
            $this->assertSame(1, Artisan::call('pass:check-production', ['--railway' => true]));
            $output = Artisan::output();
            foreach (['APP_DEBUG', 'Development gate simulation', 'FRONTEND_URL', 'Tesseract', 'PDF text extraction'] as $name) {
                $this->assertStringContainsString('FAIL: '.$name, $output);
            }
            $this->assertStringNotContainsString('SECRET', $output);
        }
    }

    public function test_missing_delivery_volume_queue_and_persistent_rate_limits_block_startup(): void
    {
        $this->productionConfig();
        config(['mail.default' => 'log', 'pass.deployment.volume_mount_path' => '', 'queue.default' => 'database', 'cache.default' => 'array']);
        $this->assertSame(1, Artisan::call('pass:check-production', ['--railway' => true]));
        $output = Artisan::output();
        foreach (['A real mail transport', 'A Railway volume', 'The single-service Railway profile', 'Rate limits'] as $name) {
            $this->assertStringContainsString('FAIL: '.$name, $output);
        }
    }

    public function test_production_reference_seed_preserves_existing_people_tags_and_slot_configuration(): void
    {
        // This test checks preservation of existing records, including custom departments.
        // Empty-table initialization is checked separately below.
        DB::table('departments')->insert([
            'department_code' => 'QA-KEEP',
            'department_name' => 'EXISTING CAMPUS DEPARTMENT',
            'is_active' => false,
        ]);
        $vehicle = $this->campusVehicle();
        $slot = DB::table('parking_slots')->where('slot_code', 'C-01')->first();
        DB::table('parking_slots')->where('slot_id', $slot->slot_id)->update(['slot_status' => 'maintenance']);
        DB::table('parking_areas')->where('area_code', 'CAR')->update(['area_name' => 'CAMPUS CUSTOM NAME']);
        $tables = ['users', 'rfid_tags', 'rfid_assignments', 'parking_slots', 'parking_areas', 'parking_zones', 'roles', 'departments', 'vehicle_types', 'violation_types'];
        $before = [];
        foreach ($tables as $table) $before[$table] = DB::table($table)->get()->toJson();
        $this->seed(ProductionReferenceSeeder::class);
        $this->seed(ProductionReferenceSeeder::class);
        foreach ($tables as $table) $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table.' was changed');
        $this->assertDatabaseHas('rfid_tags', ['tag_uid' => $vehicle['tag']]);
    }

    public function test_empty_reference_tables_are_initialized_without_demo_accounts(): void
    {
        // These tables have no dependent rows in the isolated seeded test database.
        foreach (['parking_slots', 'parking_zones', 'access_gates', 'parking_areas', 'vehicle_types', 'roles', 'departments', 'violation_types'] as $table) DB::table($table)->delete();
        $users = DB::table('users')->count();
        $this->seed(ProductionReferenceSeeder::class);
        $this->assertSame(139, DB::table('parking_slots')->count());
        $this->assertSame(2, DB::table('parking_areas')->count());
        $this->assertSame(6, DB::table('roles')->count());
        $this->assertSame(10, DB::table('departments')->count());
        $this->assertSame($users, DB::table('users')->count());
        $this->assertSame(0, DB::table('rfid_tags')->count());
    }
}
