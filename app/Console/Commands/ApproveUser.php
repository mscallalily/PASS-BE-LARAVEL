<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ApproveUser extends Command
{
    protected $signature = 'pass:approve {email?}';
    protected $description = 'List pending users, or approve one by email';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! $email) {
            $pending = User::where('account_status', 'pending')->get(['user_id', 'email', 'user_code', 'user_type']);
            if ($pending->isEmpty()) {
                $this->info('Walang pending na user.');
                return self::SUCCESS;
            }
            $this->table(['ID', 'Email', 'User code', 'Type'], $pending->map(fn ($u) => [$u->user_id, $u->email, $u->user_code, $u->user_type])->all());
            $this->line('Para i-approve: php artisan pass:approve EMAIL');
            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("Walang user na may email na: {$email}");
            return self::FAILURE;
        }

        $user->update(['account_status' => 'active']);
        $this->info("Approved na: {$user->email} (status: {$user->account_status})");
        return self::SUCCESS;
    }
}