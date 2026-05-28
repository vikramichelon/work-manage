<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'user:role {email : The user\'s email address} {role : One of admin|manager|member}';

    protected $description = 'Set a user\'s role (admin, manager, or member)';

    public function handle(): int
    {
        $role = Role::tryFrom(strtolower($this->argument('role')));

        if (! $role) {
            $this->error('Invalid role. Use one of: '.implode(', ', array_column(Role::cases(), 'value')));

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email [{$this->argument('email')}].");

            return self::FAILURE;
        }

        $user->update(['role' => $role]);

        $this->info("{$user->name} ({$user->email}) is now a {$role->label()}.");

        return self::SUCCESS;
    }
}
