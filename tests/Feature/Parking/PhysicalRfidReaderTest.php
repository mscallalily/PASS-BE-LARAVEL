<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Pass\UsbRfid\BridgeFiles;
use Pass\UsbRfid\ReaderSetup;
use RuntimeException;

require_once __DIR__.'/../../../tools/rfid/Setup-PassUsbReader.php';

class PhysicalRfidReaderTest extends ParkingTestCase
{
    private function reader(array $overrides = []): array
    {
        $key = bin2hex(random_bytes(32));
        $id = DB::table('rfid_readers')->insertGetId($overrides + [
            'reader_code' => 'PHYSICAL-TEST', 'gate_id' => DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->value('gate_id'),
            'api_key_hash' => Hash::make($key), 'reader_direction' => 'both', 'reader_status' => 'offline',
            'created_at' => now(), 'updated_at' => now(),
        ], 'reader_id');
        return ['id' => $id, 'headers' => ['X-Reader-Code' => $overrides['reader_code'] ?? 'PHYSICAL-TEST', 'X-Reader-Key' => $key]];
    }

    public function test_real_reader_authentication_is_required_without_a_user_session(): void
    {
        $reader = $this->reader();
        $payload = ['tag_uid' => str_repeat('A', 24), 'direction' => 'in'];
        $this->postJson('/api/devices/rfid/scan', $payload)->assertUnauthorized();
        $this->postJson('/api/devices/rfid/scan', $payload, ['X-Reader-Code' => 'PHYSICAL-TEST', 'X-Reader-Key' => 'wrong'])->assertUnauthorized();
        $this->assertSame(0, DB::table('parking_transactions')->count());
        $this->assertNull(DB::table('rfid_readers')->where('reader_id', $reader['id'])->value('last_heartbeat_at'));
    }

    public function test_physical_motorcycle_entry_repeat_and_exit_use_real_records_and_the_motorcycle_lot(): void
    {
        config(['pass.simulator_enabled' => false]);
        $vehicle = $this->campusVehicle('motorcycle'); $reader = $this->reader();
        $tag = str_repeat('B', 24); DB::table('rfid_tags')->where('tag_uid', $vehicle['tag'])->update(['tag_uid' => $tag]);
        $entry = $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $tag, 'direction' => 'in'], $reader['headers'])
            ->assertOk()->assertJsonPath('status', 'granted')->assertJsonPath('plate_number', $vehicle['plate']);
        $slot = $entry->json('slot_code'); $this->assertStringStartsWith('M-', $slot);
        $event = DB::table('access_events')->where('vehicle_id', $vehicle['vehicle_id'])->first();
        $this->assertSame($reader['id'], (int) $event->reader_id);
        $this->assertSame('reader', json_decode($event->raw_payload, true)['source']);
        $this->assertNull($event->processed_by_user_id);
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $tag, 'direction' => 'in'], $reader['headers'])->assertOk()->assertJsonPath('status', 'already_inside');
        $this->assertSame(1, DB::table('parking_transactions')->where('transaction_status', 'active')->count());
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $tag, 'direction' => 'out'], $reader['headers'])->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('slot_code', $slot);
        $this->assertSame(0, DB::table('parking_transactions')->where('transaction_status', 'active')->count());
        $this->assertSame(2, DB::table('access_events')->where('vehicle_id', $vehicle['vehicle_id'])->count());
        $this->assertSame('online', DB::table('rfid_readers')->where('reader_id', $reader['id'])->value('reader_status'));
    }

    public function test_unknown_tags_record_denial_without_allocating_a_slot(): void
    {
        $reader = $this->reader();
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => str_repeat('C', 24), 'direction' => 'in'], $reader['headers'])->assertOk()->assertJsonPath('status', 'denied');
        $this->assertSame(0, DB::table('parking_transactions')->count());
        $this->assertSame(1, DB::table('access_events')->where('reader_id', $reader['id'])->where('auth_result', 'denied')->count());
    }

    public function test_disabled_reader_or_gate_and_missing_direction_cannot_create_parking_events(): void
    {
        $reader = $this->reader(); $payload = ['tag_uid' => str_repeat('D', 24), 'direction' => 'in'];
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $payload['tag_uid']], $reader['headers'])->assertUnprocessable();
        DB::table('rfid_readers')->where('reader_id', $reader['id'])->update(['reader_status' => 'maintenance']);
        $this->postJson('/api/devices/rfid/scan', $payload, $reader['headers'])->assertStatus(423);
        DB::table('rfid_readers')->where('reader_id', $reader['id'])->update(['reader_status' => 'offline']);
        DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->update(['gate_status' => 'offline']);
        $this->postJson('/api/devices/rfid/scan', $payload, $reader['headers'])->assertStatus(423);
        DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->update(['gate_status' => 'online', 'gate_type' => 'exit']);
        $this->postJson('/api/devices/rfid/scan', $payload, $reader['headers'])->assertUnprocessable();
        $this->assertSame(0, DB::table('access_events')->count());
    }

    public function test_physical_reader_obeys_latest_authorization_and_selected_vehicle(): void
    {
        $reader = $this->reader(); $vehicle = $this->campusVehicle();
        DB::table('vehicle_authorizations')->insert(['vehicle_id' => $vehicle['vehicle_id'], 'authorization_status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $vehicle['tag'], 'direction' => 'in'], $reader['headers'])->assertOk()->assertJsonPath('status', 'denied');
        $other = $this->campusVehicle(); DB::table('user_vehicle_selections')->where('user_id', $other['user']->getKey())->delete();
        $this->postJson('/api/devices/rfid/scan', ['tag_uid' => $other['tag'], 'direction' => 'in'], $reader['headers'])->assertOk()->assertJsonPath('status', 'denied');
        $this->assertSame(0, DB::table('parking_transactions')->count());
    }

    private function project(): string
    {
        $path = sys_get_temp_dir().'/pass-reader-test-'.bin2hex(random_bytes(6)); mkdir($path.'/.git', 0700, true); return $path;
    }
    private function removeProject(string $path): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); } rmdir($path);
    }

    public function test_local_setup_preview_and_repeat_preserve_credentials_assignments_and_parking_records(): void
    {
        $project = $this->project();
        try {
            $vehicle = $this->campusVehicle();
            $before = DB::table('rfid_assignments')->get()->toJson();
            $preview = ReaderSetup::configure($project, false, testHarness: true);
            $this->assertFalse($preview['applied']); $this->assertDirectoryDoesNotExist($project.'/.git/pass-usb-rfid');
            $this->assertSame(0, DB::table('rfid_readers')->where('reader_code', ReaderSetup::CODE)->count());
            $first = ReaderSetup::configure($project, true, testHarness: true); $this->assertTrue($first['applied']);
            $settings = BridgeFiles::config(BridgeFiles::directory($project));
            $reader = DB::table('rfid_readers')->where('reader_code', ReaderSetup::CODE)->first();
            $this->assertNotSame($settings['reader_key'], $reader->api_key_hash); $this->assertTrue(Hash::check($settings['reader_key'], $reader->api_key_hash));
            $this->assertSame('offline', $reader->reader_status);
            ReaderSetup::configure($project, true, testHarness: true);
            $this->assertSame($reader->api_key_hash, DB::table('rfid_readers')->where('reader_code', ReaderSetup::CODE)->value('api_key_hash'));
            $this->assertSame($settings['reader_key'], BridgeFiles::config(BridgeFiles::directory($project))['reader_key']);
            $this->assertSame($before, DB::table('rfid_assignments')->get()->toJson());
            $this->assertSame(0, DB::table('parking_transactions')->count());
            if (PHP_OS_FAMILY !== 'Windows') $this->assertSame(0600, fileperms($project.'/.git/pass-usb-rfid/reader.json') & 0777);
        } finally { $this->removeProject($project); }
    }

    public function test_setup_refuses_existing_unknown_credentials_instead_of_resetting_a_reader(): void
    {
        $project = $this->project(); $reader = $this->reader(['reader_code' => ReaderSetup::CODE]);
        try {
            $before = DB::table('rfid_readers')->where('reader_id', $reader['id'])->value('api_key_hash');
            try { ReaderSetup::configure($project, true, testHarness: true); $this->fail('Unknown reader credentials were replaced.'); }
            catch (RuntimeException $e) { $this->assertStringContainsString('not replaced', $e->getMessage()); }
            $this->assertSame($before, DB::table('rfid_readers')->where('reader_id', $reader['id'])->value('api_key_hash'));
            $this->assertDirectoryDoesNotExist($project.'/.git/pass-usb-rfid');
        } finally { $this->removeProject($project); }
    }
}
