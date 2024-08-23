<?php
namespace DatabaseBackupManager\Providers;

use Illuminate\Support\ServiceProvider;
use DatabaseBackupManager\Console\Commands\BackupDatabase;

class BackupServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            BackupDatabase::class,
        ]);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/backup.php' => config_path('backup.php'),
        ], 'config');
    }
}