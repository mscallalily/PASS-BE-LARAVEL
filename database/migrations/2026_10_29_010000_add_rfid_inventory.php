<?php

use App\Support\RecordHistoryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFID inventory on the existing rfid_tags / rfid_assignments tables. Additive
 * and nullable: existing tags, assignments and vehicles are unchanged.
 *  - rfid_tags.inventory_*: a physical sticker registered in the Admin inventory.
 *    Only these are assigned automatically.
 *  - rfid_assignments.issued_*: Admin confirmed the physical hand-over
 *    (separate from the database assignment).
 *  - vehicles.rfid_requested_at: set only by a new campus registration; only
 *    such vehicles can receive an inventory sticker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfid_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('rfid_tags', 'inventory_registered_at')) $table->dateTime('inventory_registered_at')->nullable()->after('acquired_at');
            if (!Schema::hasColumn('rfid_tags', 'inventory_registered_by')) $table->unsignedBigInteger('inventory_registered_by')->nullable()->after('inventory_registered_at');
            if (!Schema::hasColumn('rfid_tags', 'inventory_source')) $table->string('inventory_source', 10)->nullable()->after('inventory_registered_by');
        });
        Schema::table('rfid_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('rfid_assignments', 'assignment_source')) $table->string('assignment_source', 20)->nullable()->after('assigned_at');
            if (!Schema::hasColumn('rfid_assignments', 'issued_at')) $table->dateTime('issued_at')->nullable()->after('assignment_source');
            if (!Schema::hasColumn('rfid_assignments', 'issued_by')) $table->unsignedBigInteger('issued_by')->nullable()->after('issued_at');
        });
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'rfid_requested_at')) $table->dateTime('rfid_requested_at')->nullable()->after('registration_status');
        });
        $this->foreign('rfid_tags', 'inventory_registered_by', 'fk_rfid_tags_inventory_registered_by');
        $this->foreign('rfid_assignments', 'issued_by', 'fk_rfid_assignments_issued_by');
        if (!collect(Schema::getIndexes('rfid_tags'))->contains('name', 'idx_rfid_tags_inventory')) {
            Schema::table('rfid_tags', fn (Blueprint $table) => $table->index(['tag_status', 'inventory_registered_at'], 'idx_rfid_tags_inventory'));
        }
        $this->rebuildHistory(['rfid_tags', 'rfid_assignments', 'vehicles']);
    }

    public function down(): void
    {
        // Retain inventory and issuance history when code is rolled back.
    }

    private function foreign(string $table, string $column, string $name): void
    {
        if (collect(Schema::getForeignKeys($table))->contains('name', $name)) return;
        Schema::table($table, fn (Blueprint $t) => $t->foreign($column, $name)->references('user_id')->on('users'));
    }

    /** History triggers snapshot an explicit column list; rebuild them so the new columns are retained. */
    private function rebuildHistory(array $tables): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || !Schema::hasTable('record_history')) return;
        foreach ($tables as $table) {
            $columns = array_values(array_filter(Schema::getColumnListing($table), fn ($c) => RecordHistoryCatalog::retainedColumn($table, $c)));
            $primary = collect(Schema::getIndexes($table))->firstWhere('primary', true)['columns'];
            $id = fn ($prefix) => "CONCAT_WS(':', ".implode(', ', array_map(fn ($c) => "$prefix.`$c`", $primary)).')';
            $snapshot = fn ($prefix) => 'JSON_OBJECT('.implode(', ', array_map(fn ($c) => "'$c', $prefix.`$c`", $columns)).')';
            foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
                $name = 'pass_history_'.$table.'_'.strtolower($event);
                DB::unprepared("DROP TRIGGER IF EXISTS `$name`");
                $prefix = $event === 'DELETE' ? 'OLD' : 'NEW';
                $before = $event === 'INSERT' ? 'NULL' : $snapshot('OLD');
                $after = $event === 'DELETE' ? 'NULL' : $snapshot('NEW');
                $operation = match ($event) { 'INSERT' => "'created'", 'DELETE' => "'deleted'", default => "'updated'" };
                if ($event === 'UPDATE' && in_array('deleted_at', $columns, true)) $operation = "CASE WHEN OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN 'archived' WHEN OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN 'restored' ELSE 'updated' END";
                $statement = "INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                    VALUES ('$table', {$id($prefix)}, $operation, @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), $before, $after, CURRENT_TIMESTAMP(6));";
                if ($event === 'UPDATE') $statement = 'IF '.implode(' OR ', array_map(fn ($c) => "NOT (OLD.`$c` <=> NEW.`$c`)", $columns))." THEN $statement END IF;";
                DB::unprepared("CREATE TRIGGER `$name` AFTER $event ON `$table` FOR EACH ROW BEGIN $statement END");
            }
        }
    }
};
