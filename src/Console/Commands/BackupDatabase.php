<?php

namespace DatabaseBackupManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--format=sql}';
    protected $description = 'Backup the database in specified format';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $format = $this->option('format');
        $database = config('database.connections.mysql.database');
        $filename = $database . '-' . date('Y-m-d-H-i-s') . '.' . $format;
        $filepath = storage_path('app/backups/' . $filename);

        if ($format === 'sql') {
            $this->backupAsSql($filepath);
        } else {
            $this->error('Unsupported format: ' . $format);
            return 1;
        }

        $this->info('Database backup completed: ' . $filepath);
    }

    protected function backupAsSql($filepath)
    {
        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $filepath
        );

        system($command);
    }
}
