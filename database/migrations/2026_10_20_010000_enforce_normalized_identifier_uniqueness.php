<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check BOTH tables before any DDL. Never merge conflicting owners or
        // discard history to make a migration pass. MySQL DDL auto-commits.
        foreach (['vehicles' => 'plate_number', 'users' => 'user_code'] as $table => $source) {
            $expression = "REPLACE(REPLACE(UPPER(`{$source}`), '-', ''), ' ', '')";
            if (DB::table($table)->selectRaw("{$expression} AS normalized_value, COUNT(*) AS copies")->groupByRaw($expression)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Resolve duplicate normalized identifiers in {$table} with an ownership review before migrating. No records were removed.");
            }
        }
        foreach (['vehicles' => ['plate_number', 'plate_key', 20], 'users' => ['user_code', 'user_code_key', 50]] as $table => [$source, $target, $length]) {
            if (!Schema::hasColumn($table, $target)) Schema::table($table, fn (Blueprint $t) => $t->string($target, $length)->storedAs("REPLACE(REPLACE(UPPER(`{$source}`), '-', ''), ' ', '')"));
            if (!Schema::hasIndex($table, $table.'_'.$target.'_unique')) Schema::table($table, fn (Blueprint $t) => $t->unique($target));
        }
    }
    public function down(): void
    {
        foreach (['vehicles' => 'plate_key', 'users' => 'user_code_key'] as $table => $target) {
            if (Schema::hasIndex($table, $table.'_'.$target.'_unique')) Schema::table($table, fn (Blueprint $t) => $t->dropUnique([$target]));
            if (Schema::hasColumn($table, $target)) Schema::table($table, fn (Blueprint $t) => $t->dropColumn($target));
        }
    }
};
