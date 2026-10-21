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
        if (! Schema::hasColumn('user_profiles', 'section')) {
            Schema::table('user_profiles', fn (Blueprint $table) => $table->string('section', 60)->nullable()->after('year_level'));
        }
        if (DB::connection()->getDriverName() !== 'mysql' || ! Schema::hasTable('record_history')) return;
        // Rebuild this table's snapshots so new academic fields are retained too.
        $columns = array_values(array_filter(Schema::getColumnListing('user_profiles'), fn ($c) => RecordHistoryCatalog::retainedColumn('user_profiles', $c)));
        $snapshot = fn ($prefix) => 'JSON_OBJECT('.implode(', ', array_map(fn ($c) => "'$c', $prefix.`$c`", $columns)).')';
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $name = 'pass_history_user_profiles_'.strtolower($event);
            DB::unprepared("DROP TRIGGER IF EXISTS `$name`");
            $prefix = $event === 'DELETE' ? 'OLD' : 'NEW';
            $before = $event === 'INSERT' ? 'NULL' : $snapshot('OLD');
            $after = $event === 'DELETE' ? 'NULL' : $snapshot('NEW');
            $operation = match ($event) { 'INSERT' => 'created', 'DELETE' => 'deleted', default => 'updated' };
            $statement = "INSERT INTO record_history (record_type, record_id, operation, actor_user_id, actor_label, source, ip_address, before_values, after_values, recorded_at)
                VALUES ('user_profiles', {$prefix}.profile_id, '$operation', @pass_history_actor, LEFT(@pass_history_label, 100), LEFT(@pass_history_source, 150), LEFT(@pass_history_ip, 45), $before, $after, CURRENT_TIMESTAMP(6));";
            if ($event === 'UPDATE') $statement = 'IF '.implode(' OR ', array_map(fn ($c) => "NOT (OLD.`$c` <=> NEW.`$c`)", $columns))." THEN $statement END IF;";
            DB::unprepared("CREATE TRIGGER `$name` AFTER $event ON user_profiles FOR EACH ROW BEGIN $statement END");
        }
    }

    public function down(): void
    {
        // Retain previously recorded academic details when code is rolled back.
    }
};
