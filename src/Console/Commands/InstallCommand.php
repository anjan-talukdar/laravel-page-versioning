<?php

namespace AnjanTalukdar\PageVersioning\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'page-versioning:install';

    protected $description = 'Install the base Page & Page Versioning package assets and migrations';

    public function handle(): int
    {
        $this->info('Installing Page & Page Versioning Package...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'page-versioning-config',
            '--force' => false,
        ]);

        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'page-versioning-migrations',
            '--force' => false,
        ]);

        if ($this->confirm('Would you like to run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('Page & Page Versioning package installed successfully!');
        $this->comment('Tip: If you use Filament Admin, run "php artisan page-versioning:install-filament" to integrate the admin plugin.');

        return self::SUCCESS;
    }
}
