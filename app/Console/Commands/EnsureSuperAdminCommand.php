<?php

namespace App\Console\Commands;

use App\Actions\Auth\EnsureSuperAdmin;
use Illuminate\Console\Command;

class EnsureSuperAdminCommand extends Command
{
    protected $signature = 'healthlog:ensure-super-admin';

    protected $description = 'Create or promote the operator account for the Superadmin dashboard';

    public function handle(EnsureSuperAdmin $ensure): int
    {
        $user = $ensure->handle();

        $this->info('Operator account ready: '.$user->email);

        return self::SUCCESS;
    }
}
