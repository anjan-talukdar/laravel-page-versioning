<?php

namespace AnjanTalukdar\PageVersioning\Console\Commands;

use Illuminate\Console\Command;

class InstallFilamentCommand extends Command
{
    protected $signature = 'page-versioning:install-filament {--publish-resources : Publish Filament resource classes into app/Filament/Resources/PageVersioning}';

    protected $description = 'Install and display setup guidance for Filament Admin Page Versioning Plugin';

    public function handle(): int
    {
        $this->info('Configuring Filament Admin Plugin for Page Versioning...');

        if (!class_exists('Filament\FilamentManager')) {
            $this->error('Filament package (filament/filament) was not detected in this project.');
            $this->comment('Please install Filament first via: composer require filament/filament');
            return self::FAILURE;
        }

        $shouldPublish = $this->option('publish-resources')
            || $this->confirm('Would you like to publish the Filament resource files to app/Filament/Resources/PageVersioning for full customization?', false);

        if ($shouldPublish) {
            $this->info('Publishing Filament resources...');
            $this->call('vendor:publish', [
                '--tag' => 'page-versioning-filament',
                '--force' => false,
            ]);
            $this->info('Filament resources published to app/Filament/Resources/PageVersioning!');
        }

        $this->newLine();
        $this->info('Filament detected!');
        $this->line('To complete the Filament setup, register PageVersioningPlugin in your Panel Provider (e.g., app/Providers/Filament/AdminPanelProvider.php):');
        $this->newLine();

        $this->comment('--------------------------------------------------------------------------------');
        $this->line('use AnjanTalukdar\PageVersioning\Filament\PageVersioningPlugin;');
        $this->newLine();
        $this->line('public function panel(Panel $panel): Panel');
        $this->line('{');
        $this->line('    return $panel');
        $this->line('        // ...');
        $this->line('        ->plugin(');
        $this->line('            PageVersioningPlugin::make()');
        $this->line('                ->navigationGroup("Content Management")');
        $this->line('                ->navigationIcon("heroicon-o-document-duplicate")');
        $this->line('        );');
        $this->line('}');
        $this->comment('--------------------------------------------------------------------------------');

        $this->newLine();
        $this->info('Filament installation guidance ready!');

        return self::SUCCESS;
    }
}
