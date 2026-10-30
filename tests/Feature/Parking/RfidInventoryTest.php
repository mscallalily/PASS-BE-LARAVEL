<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\Rfid\RfidInventory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Admin RFID inventory: stickers scanned with the USB reader, automatic
 * assignment to new eligible registrations only, and physical issuance.
 * Runs in the isolated *_testing database inside a rolled-back transaction.
 */
class RfidInventoryTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Storage::fake('private'); Cache::flush(); config(['queue.default' => 'sync']);
    }

    /** A 24-character EPC as the USB reader types it. */
    private function epc(): string
    {
        return 'E28068940000'.strtoupper(bin2hex(random_bytes(6)));
    }

    private function registerSticker(?string $uid = null): string
    {
        $uid ??= $this->epc();
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $uid, 'source' => 'reader'])->assertCreated();
        return $uid;
    }

    /** An active student with a verified license and no vehicle yet. */
    private function campusOwner(string $type = 'student'): User
    {
        $user = $this->user($type);
        $user->roles()->attach(DB::table('roles')->where('role_code', $type)->value('role_id'), ['assigned_at' => now()]);
        $driver = DB::table('drivers')->insertGetId(['user_id' => $user->user_id, 'first_name' => 'Ina', 'last_name' => 'Ventory', 'driver_status' => 'active', 'created_at' => now(), 'updated_at' => now()], 'driver_id');
        DB::table('driver_licenses')->insert(['driver_id' => $driver, 'license_number' => 'N'.random_int(10, 99).'-'.random_int(10, 99).'-'.random_int(100000, 999999),
            'license_type' => 'non_professional', 'license_status' => 'valid', 'verification_status' => 'verified', 'expiry_date' => now()->addYears(3)->toDateString(), 'created_at' => now(), 'updated_at' => now()]);
        return $user;
    }

    /** A new campus registration through the real OR/CR flow (automatic approval). Returns the vehicle ID. */
    private function newRegistration(?User $owner = null): int
    {
        $owner ??= $this->campusOwner();
        Sanctum::actingAs($owner);
        $plate = random_int(100, 999).chr(random_int(65, 90)).chr(random_int(65, 90)).chr(random_int(65, 90));
        $or = "LAND TRANSPORTATION OFFICE\nOFFICIAL RECEIPT 1303-000000297665\nPlate No: {$plate} | MVUC PHP 720.00\nVehicle Type: Motorcycle Without Sidecar\n"
            ."Color: BLACK\nThis payment is valid until\n".now()->addYear()->format('m/Y')." and due for renewal on\n".now()->addYear()->format('m/d/Y').'.';
        $cr = "LAND TRANSPORTATION OFFICE\nCERTIFICATE OF REGISTRATION\nPLATE NO. {$plate} ENGINE NO. CGA1-779901\nMAKE: SUZUKI SERIES: FU150MFZ\nCOLOR: BLACK\n"
            ."BODY TYPE: MOTORCYCLE WITHOUT SIDECAR\nYEAR MODEL: 2024\naccompanied by Official Receipt as proof of payment";
        $image = fn ($text) => ['kind' => 'image', 'engine_available' => true, 'engine_error' => null, 'text' => $text, 'confidence' => 80.0, 'width' => 2048, 'height' => 1432, 'sharpness' => 1900.0];
        $this->mock(LicenseDocumentReader::class)->shouldReceive('read')->times(2)->andReturn($image($or), $image($cr));
        return $this->post('/api/vehicles', ['plate_number' => $plate, 'vehicle_type' => 'Motorcycle', 'make' => 'SUZUKI', 'model' => 'FU150MFZ', 'color' => 'BLACK', 'year_model' => 2024,
            'or_document' => UploadedFile::fake()->image('or.jpg', 2048, 1432), 'cr_document' => UploadedFile::fake()->image('cr.jpg', 2048, 1538)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('authorization_status', 'approved')->json('vehicle_id');
    }

    private function inventoryRow(string $uid): array
    {
        $this->actingAsAdmin();
        return collect($this->getJson('/api/rfid/inventory')->assertOk()->json('data'))->firstWhere('tag_uid', $uid);
    }

    private function activeAssignment(int $vehicleId): ?object
    {
        return DB::table('rfid_assignments')->where('vehicle_id', $vehicleId)->where('assignment_status', 'active')->first();
    }

    private function rfidStatus(User $owner, int $vehicleId): ?string
    {
        Sanctum::actingAs($owner);
        return collect($this->getJson('/api/vehicles')->assertOk()->json('vehicles'))->firstWhere('id', (string) $vehicleId)['rfidStatus'];
    }

    public function test_only_admin_registers_a_scanned_uid_and_it_starts_available(): void
    {
        $uid = $this->epc();
        $this->actingAsStaff();
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $uid, 'source' => 'reader'])->assertForbidden();
        $this->getJson('/api/rfid/inventory')->assertForbidden();
        Sanctum::actingAs($this->campusOwner());
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $uid, 'source' => 'reader'])->assertForbidden();
        $this->assertDatabaseMissing('rfid_tags', ['tag_uid' => $uid]);

        $admin = $this->actingAsAdmin();
        // The reader types lowercase or padded frames in some layouts; the stored UID is normalized.
        $this->postJson('/api/rfid/inventory', ['tag_uid' => ' '.strtolower($uid).' ', 'source' => 'reader'])->assertCreated()->assertJsonPath('assigned_plate', null);
        $tag = DB::table('rfid_tags')->where('tag_uid', $uid)->first();
        $this->assertSame(['available', 'sticker', 'reader', $admin->user_id], [$tag->tag_status, $tag->tag_type, $tag->inventory_source, (int) $tag->inventory_registered_by]);
        $this->assertNotNull($tag->inventory_registered_at);
        $this->assertSame('available', $this->inventoryRow($uid)['status']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'rfid_inventory_registered', 'auditable_id' => $tag->rfid_id]);
        // An incomplete reader frame is not a UID.
        $this->postJson('/api/rfid/inventory', ['tag_uid' => substr($this->epc(), 0, 23), 'source' => 'reader'])->assertUnprocessable()->assertJsonValidationErrors('tag_uid');
        // A real UID typed by hand is accepted only as hexadecimal and is labelled manual.
        $this->postJson('/api/rfid/inventory', ['tag_uid' => 'NOT-A-UID', 'source' => 'manual'])->assertUnprocessable();
        $manual = 'E2806894'.strtoupper(bin2hex(random_bytes(4)));
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $manual, 'source' => 'manual'])->assertCreated();
        $this->assertSame('manual', DB::table('rfid_tags')->where('tag_uid', $manual)->value('inventory_source'));
    }

    public function test_duplicate_uids_are_rejected_and_existing_tags_are_not_overwritten(): void
    {
        $uid = $this->registerSticker();
        $this->postJson('/api/rfid/inventory', ['tag_uid' => strtolower($uid), 'source' => 'reader'])->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_uid' => 'already in the RFID inventory']);
        $this->assertSame(1, DB::table('rfid_tags')->where('tag_uid', $uid)->count());

        // A UID already linked to an existing (sample) vehicle: reported, record preserved.
        $car = $this->campusVehicle();
        $existing = $this->epc();
        DB::table('rfid_tags')->where('tag_uid', $car['tag'])->update(['tag_uid' => $existing]);
        $before = DB::table('rfid_tags')->where('tag_uid', $existing)->first();
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $existing, 'source' => 'reader'])->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_uid' => "assigned to {$car['plate']}"]);
        $this->assertEquals($before, DB::table('rfid_tags')->where('tag_uid', $existing)->first());
        $this->assertNotNull($this->activeAssignment($car['vehicle_id']));
    }

    public function test_ten_physical_sticker_uids_register_without_duplicates(): void
    {
        $uids = [];
        while (count($uids) < 10) $uids[] = $this->registerSticker();
        $this->actingAsAdmin();
        $inventory = $this->getJson('/api/rfid/inventory')->assertOk();
        $rows = collect($inventory->json('data'))->whereIn('tag_uid', $uids);
        $this->assertCount(10, $rows);
        $this->assertCount(10, $rows->pluck('tag_uid')->unique());
        $this->assertSame(['available'], $rows->pluck('status')->unique()->values()->all());
        $this->assertGreaterThanOrEqual(10, $inventory->json('summary.available'));
        // Registering stickers never creates assignments by itself.
        $this->assertSame(0, DB::table('rfid_assignments')->whereIn('rfid_id', DB::table('rfid_tags')->whereIn('tag_uid', $uids)->select('rfid_id'))->count());
    }

    public function test_a_new_eligible_registration_receives_an_available_sticker_automatically(): void
    {
        $uid = $this->registerSticker();
        $owner = $this->campusOwner();
        $vehicleId = $this->newRegistration($owner);

        $assignment = $this->activeAssignment($vehicleId);
        $this->assertNotNull($assignment);
        $this->assertSame($uid, DB::table('rfid_tags')->where('rfid_id', $assignment->rfid_id)->value('tag_uid'));
        $this->assertSame(['inventory', (int) $owner->user_id, null], [$assignment->assignment_source, (int) $assignment->user_id, $assignment->issued_at]);
        $this->assertSame('assigned', DB::table('rfid_tags')->where('tag_uid', $uid)->value('tag_status'));
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->user_id, 'notification_type' => 'rfid_assigned']);
        $this->assertSame('assigned', $this->rfidStatus($owner, $vehicleId));
        $vehicle = collect($this->getJson('/api/vehicles')->json('vehicles'))->firstWhere('id', (string) $vehicleId);
        $this->assertSame($uid, $vehicle['rfid']);
        $row = $this->inventoryRow($uid);
        $this->assertSame(['assigned', $vehicleId], [$row['status'], $row['current']['vehicle_id']]);
        // Re-running the assignment (e.g. a retried job) never gives the vehicle a second sticker.
        $this->registerSticker();
        $this->assertNull(app(RfidInventory::class)->assignToVehicle($vehicleId));
        $this->assertSame(1, DB::table('rfid_assignments')->where('vehicle_id', $vehicleId)->count());
    }

    public function test_existing_sample_vehicles_never_consume_inventory_and_keep_their_assignments(): void
    {
        $withTag = $this->campusVehicle();
        $withoutTag = $this->campusVehicle();
        $withoutTag['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        DB::table('rfid_assignments')->where('vehicle_id', $withoutTag['vehicle_id'])->delete();
        $before = DB::table('rfid_assignments')->where('vehicle_id', $withTag['vehicle_id'])->get();
        $uids = [$this->registerSticker(), $this->registerSticker()];

        // Approved, licensed, but not registered through the new workflow: never eligible.
        foreach ([$withTag, $withoutTag] as $car) $this->assertNull(app(RfidInventory::class)->assignToVehicle($car['vehicle_id']));
        app(RfidInventory::class)->assignPending();
        $this->assertNull($this->activeAssignment($withoutTag['vehicle_id']));
        $this->assertEquals($before, DB::table('rfid_assignments')->where('vehicle_id', $withTag['vehicle_id'])->get());
        $this->assertSame(['available'], DB::table('rfid_tags')->whereIn('tag_uid', $uids)->pluck('tag_status')->unique()->values()->all());
        // Reading the vehicle list does not assign anything either.
        Sanctum::actingAs($withoutTag['user']);
        $this->getJson('/api/vehicles')->assertOk();
        $this->assertNull($this->activeAssignment($withoutTag['vehicle_id']));
        // The manual Admin flow cannot hand a reserved sticker to an existing vehicle.
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/assignments', ['vehicle_id' => $withoutTag['vehicle_id'], 'tag_uid' => $uids[0]])->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_uid' => 'reserved in the RFID Inventory']);
        $this->assertSame('available', DB::table('rfid_tags')->where('tag_uid', $uids[0])->value('tag_status'));
    }

    public function test_guest_and_visitor_vehicles_never_consume_campus_inventory(): void
    {
        $uid = $this->registerSticker();
        $visitor = $this->visitorVehicle();
        // Even if a visitor vehicle carried the new-registration marker, it is not a campus vehicle.
        DB::table('vehicles')->where('vehicle_id', $visitor['vehicle_id'])->update(['rfid_requested_at' => now()]);
        DB::table('vehicle_authorizations')->insert(['vehicle_id' => $visitor['vehicle_id'], 'authorization_status' => 'approved', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->assertNull(app(RfidInventory::class)->assignToVehicle($visitor['vehicle_id']));
        // A guest account cannot use the campus registration endpoint at all.
        $guest = $this->user('other');
        $guest->roles()->attach(DB::table('roles')->where('role_code', 'guest')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($guest);
        $this->postJson('/api/vehicles', ['plate_number' => 'GST 1234'])->assertForbidden();
        app(RfidInventory::class)->assignPending();
        $this->assertSame('available', DB::table('rfid_tags')->where('tag_uid', $uid)->value('tag_status'));
        $this->assertFalse(app(RfidInventory::class)->pendingVehicles()->contains('vehicle_id', $visitor['vehicle_id']));
    }

    public function test_one_sticker_goes_to_one_vehicle_and_the_next_registration_waits(): void
    {
        $uid = $this->registerSticker();
        [$a, $b] = [$this->campusOwner(), $this->campusOwner()];
        $first = $this->newRegistration($a);
        $second = $this->newRegistration($b);
        $rfid = DB::table('rfid_tags')->where('tag_uid', $uid)->value('rfid_id');
        $this->assertSame(1, DB::table('rfid_assignments')->where('rfid_id', $rfid)->count());
        $this->assertSame((int) $first, (int) $this->activeAssignment($first)->vehicle_id);
        $this->assertNull($this->activeAssignment($second));
        // Repeated attempts for both vehicles cannot reuse the assigned UID.
        foreach ([$first, $second, $second] as $id) app(RfidInventory::class)->assignToVehicle($id);
        $this->assertSame(1, DB::table('rfid_assignments')->where('rfid_id', $rfid)->where('assignment_status', 'active')->count());
        $this->assertSame('pending_assignment', $this->rfidStatus($b, $second));
    }

    public function test_exhausted_inventory_leaves_the_registration_approved_and_pending_until_a_sticker_is_registered(): void
    {
        $owner = $this->campusOwner();
        // Exhaust whatever this test database holds so the registration finds nothing available.
        $available = DB::table('rfid_tags')->whereNotNull('inventory_registered_at')->where('tag_status', 'available')->pluck('rfid_id');
        DB::table('rfid_tags')->whereIn('rfid_id', $available)->update(['tag_status' => 'damaged']);
        $vehicleId = $this->newRegistration($owner);

        $this->assertNull($this->activeAssignment($vehicleId));
        $this->assertSame('approved', DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)->orderByDesc('authorization_id')->value('authorization_status'));
        $this->assertSame('pending_assignment', $this->rfidStatus($owner, $vehicleId));
        $this->actingAsAdmin();
        $this->assertTrue(collect($this->getJson('/api/rfid/inventory')->json('pending'))->contains('vehicle_id', $vehicleId));
        // The next registered sticker goes to the waiting registration.
        $uid = $this->epc();
        $this->postJson('/api/rfid/inventory', ['tag_uid' => $uid, 'source' => 'reader'])->assertCreated()
            ->assertJsonPath('assigned_plate', DB::table('vehicles')->where('vehicle_id', $vehicleId)->value('plate_number'));
        $this->assertSame($uid, DB::table('rfid_tags')->where('rfid_id', $this->activeAssignment($vehicleId)->rfid_id)->value('tag_uid'));
        $this->assertSame('assigned', $this->rfidStatus($owner, $vehicleId));
    }

    public function test_assignment_and_physical_issuance_are_separate_and_the_gate_waits_for_issuance(): void
    {
        $uid = $this->registerSticker();
        $owner = $this->campusOwner();
        $vehicleId = $this->newRegistration($owner);

        $this->actingAsStaff();
        $denied = $this->gate('entry', ['rfid_tag_uid' => $uid]);
        $this->assertSame('denied', $denied['status']);
        $this->assertStringContainsString('not been issued', $denied['reason']);
        $this->assertNull($this->activeTransaction($vehicleId));
        $rfid = DB::table('rfid_tags')->where('tag_uid', $uid)->value('rfid_id');
        $this->postJson("/api/rfid/inventory/{$rfid}/issue")->assertForbidden();

        $admin = $this->actingAsAdmin();
        $this->postJson("/api/rfid/inventory/{$rfid}/issue")->assertOk();
        $assignment = $this->activeAssignment($vehicleId);
        $this->assertSame((int) $admin->user_id, (int) $assignment->issued_by);
        $this->assertNotNull($assignment->issued_at);
        $this->postJson("/api/rfid/inventory/{$rfid}/issue")->assertStatus(409);
        $this->assertSame(['issued', $admin->user_code], [$this->inventoryRow($uid)['status'], $this->inventoryRow($uid)['current']['issued_by']]);
        $this->assertSame('issued', $this->rfidStatus($owner, $vehicleId));
        // An available sticker cannot be "issued".
        $spare = $this->registerSticker();
        $this->postJson('/api/rfid/inventory/'.DB::table('rfid_tags')->where('tag_uid', $spare)->value('rfid_id').'/issue')->assertStatus(409);
    }

    public function test_unknown_unassigned_revoked_and_lost_stickers_are_denied_and_history_is_kept(): void
    {
        $this->actingAsStaff();
        $this->assertSame('Unknown RFID tag.', $this->gate('entry', ['rfid_tag_uid' => $this->epc()])['reason']);
        $spare = $this->registerSticker();
        $this->actingAsStaff();
        $this->assertSame('RFID tag is available.', $this->gate('entry', ['rfid_tag_uid' => $spare])['reason']);

        // Lost with replacement: the old assignment is kept as "replaced" and a new sticker is assigned.
        $owner = $this->campusOwner();
        $vehicleId = $this->newRegistration($owner);
        $first = $this->activeAssignment($vehicleId);
        $firstUid = DB::table('rfid_tags')->where('rfid_id', $first->rfid_id)->value('tag_uid');
        $this->actingAsAdmin();
        $this->postJson("/api/rfid/inventory/{$first->rfid_id}/issue")->assertOk();
        $this->registerSticker();
        $this->postJson("/api/rfid/inventory/{$first->rfid_id}/retire", ['status' => 'lost', 'replace' => true])->assertOk()
            ->assertJsonPath('pending', false)->assertJsonPath('replacement_plate', DB::table('vehicles')->where('vehicle_id', $vehicleId)->value('plate_number'));
        $this->assertSame('lost', DB::table('rfid_tags')->where('rfid_id', $first->rfid_id)->value('tag_status'));
        $this->assertSame('replaced', DB::table('rfid_assignments')->where('assignment_id', $first->assignment_id)->value('assignment_status'));
        $replacement = $this->activeAssignment($vehicleId);
        $this->assertNotNull($replacement);
        $this->assertNotSame($first->rfid_id, $replacement->rfid_id);
        $this->assertNull($replacement->issued_at);
        $this->assertSame(2, DB::table('rfid_assignments')->where('vehicle_id', $vehicleId)->count());
        $this->assertCount(1, $this->inventoryRow($firstUid)['history']);
        $this->actingAsStaff();
        $this->assertSame('RFID tag is lost.', $this->gate('entry', ['rfid_tag_uid' => $firstUid])['reason']);

        // Revoked through the existing flow: denied, and no new sticker is handed out automatically.
        $this->actingAsAdmin();
        $this->patchJson("/api/rfid/assignments/{$replacement->assignment_id}/revoke")->assertOk();
        $revokedUid = DB::table('rfid_tags')->where('rfid_id', $replacement->rfid_id)->value('tag_uid');
        $this->assertSame('revoked', $this->inventoryRow($revokedUid)['status']);
        $this->registerSticker();
        app(RfidInventory::class)->assignPending();
        $this->assertNull($this->activeAssignment($vehicleId));
        $this->assertNull($this->rfidStatus($owner, $vehicleId));
        $this->actingAsStaff();
        $this->assertSame('RFID tag is deactivated.', $this->gate('entry', ['rfid_tag_uid' => $revokedUid])['reason']);
        $this->assertSame(0, DB::table('parking_transactions')->where('vehicle_id', $vehicleId)->count());
    }

    public function test_an_issued_sticker_enters_and_exits_with_the_existing_gate_rules(): void
    {
        $uid = $this->registerSticker();
        $owner = $this->campusOwner();
        $vehicleId = $this->newRegistration($owner);
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/inventory/'.DB::table('rfid_tags')->where('tag_uid', $uid)->value('rfid_id').'/issue')->assertOk();

        $this->actingAsStaff();
        $entry = $this->gate('entry', ['rfid_tag_uid' => $uid]);
        $this->assertSame('granted', $entry['status']);
        $transaction = $this->activeTransaction($vehicleId);
        $this->assertNotNull($transaction->slot_id);
        $slot = fn () => collect($this->getJson('/api/parking/availability-map')->assertOk()->json('slots'))->firstWhere('slot_code', $entry['slot_code'])['status'];
        $this->assertSame('occupied', $slot());
        // A repeated read does not create a second stay.
        $this->assertSame('already_inside', $this->gate('entry', ['rfid_tag_uid' => $uid])['status']);
        $this->assertSame(1, DB::table('parking_transactions')->where('vehicle_id', $vehicleId)->count());
        // Retiring a sticker while the vehicle is inside is refused.
        $this->actingAsAdmin();
        $this->postJson('/api/rfid/inventory/'.DB::table('rfid_tags')->where('tag_uid', $uid)->value('rfid_id').'/retire', ['status' => 'lost'])->assertStatus(409);
        $this->actingAsStaff();
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => $uid])['status']);
        $this->assertNull($this->activeTransaction($vehicleId));
        $this->assertSame('available', $slot());
        // Suspension still blocks an issued sticker.
        DB::table('vehicle_authorizations')->where('vehicle_id', $vehicleId)->update(['authorization_status' => 'suspended']);
        $this->assertSame('denied', $this->gate('entry', ['rfid_tag_uid' => $uid])['status']);
    }
}
