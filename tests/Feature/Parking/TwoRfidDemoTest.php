<?php
namespace Tests\Feature\Parking;

use Database\Seeders\TwoRfidDemoSeeder;
use Illuminate\Support\Facades\DB;

class TwoRfidDemoTest extends ParkingTestCase
{
    public function test_repeatable_demo_has_two_tags_one_owner_and_cannot_park_both(): void
    {
        $this->seed(TwoRfidDemoSeeder::class);
        $this->seed(TwoRfidDemoSeeder::class);
        $owner = DB::table('users')->where('email', 'two.rfid.demo@pass.test')->value('user_id');
        $this->assertSame(2, DB::table('vehicles')->where('owner_user_id', $owner)->count());
        $this->assertSame(2, DB::table('rfid_assignments')->where('user_id', $owner)->count());
        $this->assertSame(0, DB::table('parking_transactions')->where('user_id', $owner)->count());
        $this->actingAsStaff();
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-CAR-001'])['status']);
        $this->assertSame('denied', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-MC-002'])['status']);
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => 'SIM-TWO-CAR-001'])['status']);
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => 'SIM-TWO-MC-002'])['status']);
    }
}
