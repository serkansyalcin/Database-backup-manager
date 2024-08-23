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
        $directoryPath = storage_path('app/backups');
        $filepath = $directoryPath . '/' . $filename;

        // Supported formats for backup
        $supportedFormats = ['sql', 'csv', 'json'];
        if (!in_array($format, $supportedFormats)) {
            $this->error('Unsupported format: ' . $format);
            return 1;
        }

        // Ensure the backups directory exists
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // DATABASE backup process
        if ($format === 'sql') {
            $this->backupAsSql($filepath);
        } elseif ($format === 'csv') {
            $this->backupAsCsv($filepath);
        } elseif ($format === 'json') {
            $this->backupAsJson($filepath);
        }else {
            $this->error('Unsupported format: ' . $format);
            return 1;
        }

        $this->info('Database backup completed: ' . $filepath);
    }

    protected function backupAsSql($filepath)
    {
        // SQL backup creation process
        $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " " . config('database.connections.mysql.database') . " > " . $filepath;
        $result = null;
        $returnVar = null;

        exec($command, $result, $returnVar);

        if ($returnVar !== 0) {
            $this->error('Error creating SQL backup: ' . implode("\n", $result));
            return 1;
        }
    }

    protected function backupAsCsv($filepath)
    {
        $pdo = new \PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . config('database.connections.mysql.database'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $csvFile = fopen($filepath, 'w');

        foreach ($tables as $table) {
            // Write table name
            fputcsv($csvFile, [$table]);

            // Write table headers
            $headers = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_COLUMN);
            fputcsv($csvFile, $headers);

            // Write table data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                fputcsv($csvFile, $row);
            }

            // Add an empty line between tables
            fputcsv($csvFile, []);
        }

        fclose($csvFile);
    }

    protected function backupAsJson($filepath)
    {
        // JSON backup creation process
        $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " --tab=" . dirname($filepath) . " --fields-terminated-by=',' --fields-enclosed-by='\"' --fields-escaped-by='\\' --lines-terminated-by='\n' " . config('database.connections.mysql.database');
        $result = null;
        $returnVar = null;

        exec($command, $result, $returnVar);

        if ($returnVar !== 0) {
            $this->error('Error creating JSON backup: ' . implode("\n", $result));
            return 1;
        }
    }
}