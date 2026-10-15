<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;

class AccessRecordsAndSimulatorTest extends ParkingTestCase
{
    public function test_access_records_list_real_events_with_holder_tag_and_filters(): void
    {
        $this->actingAsStaff();
        $car = $this->campusVehicle();
        $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->gate('entry', ['rfid_tag_uid' => 'NO-SUCH-TAG']);

        $granted = $this->getJson('/api/access-events?method=rfid&result=granted&search=' . urlencode($car['plate']))->assertOk()->json('data');
        $this->assertCount(1, $granted);
        $this->assertSame($car['tag'], $granted[0]['rfid_tag']);
        $this->assertSame($car['user']->user_code, $granted[0]['user_code']);
        $this->assertSame('in', $granted[0]['direction']);
        $this->assertNotNull($granted[0]['transaction_id']);

        $denied = collect($this->getJson('/api/access-events?result=denied&date=today')->assertOk()->json('data'));
        $this->assertTrue($denied->contains(fn ($e) => $e['presented_credential'] === 'NO-SUCH-TAG' && $e['denial_reason'] === 'Unknown RFID tag.'));
    }

    public function test_simulator_is_hidden_when_disabled(): void
    {
        $staff = $this->actingAsStaff();
        config(['pass.simulator.enabled' => false, 'pass.simulator.testers' => [$staff->user_code]]);

        $this->getJson('/api/dev/simulator')->assertNotFound();
        $this->postJson('/api/dev/simulator/scan', ['device' => 'rfid', 'direction' => 'in', 'credential' => 'X'])->assertNotFound();
    }

    public function test_simulator_is_hidden_from_non_testers_and_in_production(): void
    {
        $this->actingAsStaff();
        config(['pass.simulator.enabled' => true, 'pass.simulator.testers' => ['SOMEONE-ELSE']]);
        $this->getJson('/api/dev/simulator')->assertNotFound();

        $staff = $this->actingAsStaff();
        config(['pass.simulator.testers' => [$staff->user_code]]);
        $this->app['env'] = 'production';
        $this->getJson('/api/dev/simulator')->assertNotFound();
    }

    public function test_simulated_scans_use_the_real_gate_rules_and_records(): void
    {
        $staff = $this->actingAsStaff();
        config(['pass.simulator.enabled' => true, 'pass.simulator.testers' => [$staff->user_code]]);
        $car = $this->campusVehicle();
        $unverified = $this->campusVehicle('sedan', 'pending_review');

        $this->getJson('/api/dev/simulator')->assertOk()->assertJsonPath('enabled', true);

        $in = $this->postJson('/api/dev/simulator/scan', ['device' => 'rfid', 'direction' => 'in', 'credential' => $car['tag']])->assertOk()->json();
        $this->assertSame('granted', $in['status']);
        $txn = $this->activeTransaction($car['vehicle_id']);
        $this->assertSame('simulator', json_decode(DB::table('access_events')->where('event_id', $txn->entry_event_id)->value('raw_payload'), true)['source']);

        // The simulator cannot bypass authorization rules.
        $this->assertSame('denied', $this->postJson('/api/dev/simulator/scan', ['device' => 'rfid', 'direction' => 'in', 'credential' => $unverified['tag']])->json('status'));

        $out = $this->postJson('/api/dev/simulator/scan', ['device' => 'rfid', 'direction' => 'out', 'credential' => $car['tag']])->assertOk()->json();
        $this->assertSame('completed', $out['status']);
        $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }
}
