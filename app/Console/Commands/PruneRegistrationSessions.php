<?php

namespace App\Console\Commands;

use App\Models\RegistrationSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneRegistrationSessions extends Command
{
    protected $signature = 'registration:prune';
    protected $description = 'Delete expired registration drafts and their temporary private photos.';

    public function handle(): int
    {
        $count = 0;
        RegistrationSession::where('expires_at', '<', now()->subMinutes(5))->orderBy('session_id')->chunkById(100, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                $paths = DB::transaction(function () use ($row, &$count) {
                    $current = RegistrationSession::whereKey($row->session_id)->lockForUpdate()->first();
                    if (!$current || $current->expires_at->gte(now()->subMinutes(5))) return [];
                    $paths = !$current->consumed_at ? array_filter([$current->document_path, $current->draft['school_id']['path'] ?? null]) : [];
                    $current->delete(); $count++;
                    return $paths;
                });
                foreach ($paths as $path) if (!DB::table('driver_licenses')->where('license_image_path', $path)->exists()
                    && !DB::table('user_profiles')->where('school_id_document_path', $path)->exists()) Storage::disk('private')->delete($path);
            }
        }, 'session_id');
        // A PHP worker may have stopped after storing a photo but before linking it to a draft.
        // Never sweep recently uploaded images or any image owned by a committed license.
        $orphans = 0;
        foreach (Storage::disk('private')->allFiles('registration-licenses') as $file) {
            if (Storage::disk('private')->lastModified($file) >= now()->subHours(2)->timestamp) continue;
            if (RegistrationSession::where('document_path', $file)->exists() || DB::table('driver_licenses')->where('license_image_path', $file)->exists()) continue;
            Storage::disk('private')->delete($file); $orphans++;
        }
        $referencedIds = [];
        foreach (RegistrationSession::whereNull('consumed_at')->cursor() as $draft) {
            if ($path = $draft->draft['school_id']['path'] ?? null) $referencedIds[$path] = true;
        }
        foreach (Storage::disk('private')->allFiles('registration-ids') as $file) {
            if (Storage::disk('private')->lastModified($file) >= now()->subHours(2)->timestamp || isset($referencedIds[$file])
                || DB::table('user_profiles')->where('school_id_document_path', $file)->exists()) continue;
            Storage::disk('private')->delete($file); $orphans++;
        }
        $this->info('Orphaned temporary photos removed: '.$orphans);
        $this->info('Expired registration drafts removed: '.$count);
        return self::SUCCESS;
    }
}
