<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminUserCommand extends Command
{
    protected $signature = 'make:admin-user {--name=Admin} {--email=admin@admin.com} {--password=password}';

    protected $description = 'Create an Admin user';

    public function handle(): void
    {
        User::query()->create($this->options());

        $this->info('User created successfully.');
    }
}
