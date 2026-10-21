<?php

use App\Support\RecordHistoryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') throw new RuntimeException('Record history requires the PASS MySQL database.');
        if (!Schema::hasTable('record_history')) Schema::create('record_history', function (Blueprint $t) {
            $t->bigIncrements('revision_id');
            $t->string('record_type', 64); $t->string('record_id', 128); $t->string('operation', 20);
            $t->unsignedBigInteger('actor_user_id')->nullable(); $t->string('actor_label', 100)->nullable();
            $t->string('source', 150)->nullable(); $t->string('ip_address', 45)->nullable();
            $t->json('before_values')->nullable(); $t->json('after_values')->nullable();
            $t->dateTime('recorded_at', 6)->useCurrent();
            $t->index(['record_type', 'record_id', 'revision_id']); $t->index('recorded_at');
        });
        if (!Schema::hasTable('audit_alert_reviews')) Schema::create('audit_alert_reviews', function (Blueprint $t) {
            $t->char('alert_key', 64)->primary(); $t->string('rule', 64); $t->string('severity', 20);
            $t->unsignedBigInteger('reviewed_by'); $t->text('note'); $t->timestamp('reviewed_at');
        });
        foreach (array_keys(RecordHistoryCatalog::ENTITIES) as $table) {
            if (!Schema::hasTable($table)) continue;
            $columns = array_values(array_filter(Schema::getColumnListing($table), fn ($c) => RecordHistoryCatalog::retainedColumn($table, $c)));
            $primary = collect(Schema::getIndexes($table))->firstWhere('primary', true)['columns'] ?? [];
            if (!$primary) throw new RuntimeException("History needs the primary key for {$table}.");
            $id = fn ($prefix) => "CONCAT_WS(':', ".implode(', ', array_map(fn ($c) => "$prefix.`$c`", $primary)).')';
            $snapshot = fn ($prefix) => 'JSON_OBJECT('.implode(', ', array_map(fn ($c) => "'$c', $prefix.`$c`", $columns)).')';
            DB::statement("INSERT INTO record_history (record_type, record_id, operation, source, after_values, recorded_at)
                SELECT '$table', {$id('r')}, 'baseline', 'history installation', {$snapshot('r')}, CURRENT_TIMESTAMP(6) FROM `$table` r
                WHERE NOT EXISTS (SELECT 1 FROM record_history h WHERE h.record_type = '$table' AND h.record_id = {$id('r')} AND h.operation = 'baseline')");
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
                if ($event === 'UPDATE') {
                    $changes = implode(' OR ', array_map(fn ($c) => "NOT (OLD.`$c` <=> NEW.`$c`)", $columns));
                    $statement = "IF $changes THEN $statement END IF;";
                }
                DB::unprepared("CREATE TRIGGER `$name` AFTER $event ON `$table` FOR EACH ROW BEGIN $statement END");
            }
        }
        foreach (['UPDATE', 'DELETE'] as $event) {
            $name = 'pass_history_immutable_'.strtolower($event);
            DB::unprepared("DROP TRIGGER IF EXISTS `$name`");
            DB::unprepared("CREATE TRIGGER `$name` BEFORE $event ON record_history FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Record history is append-only'");
        }
    }

    public function down(): void
    {
        foreach (array_keys(RecordHistoryCatalog::ENTITIES) as $table) foreach (['insert', 'update', 'delete'] as $event) DB::unprepared("DROP TRIGGER IF EXISTS `pass_history_{$table}_{$event}`");
        Schema::dropIfExists('audit_alert_reviews'); Schema::dropIfExists('record_history');
    }
};
