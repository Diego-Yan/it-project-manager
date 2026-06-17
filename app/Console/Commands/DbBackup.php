<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * [REVIEW-FIX] C4: SQLite 数据库备份命令
 *
 * 每日凌晨 3:00 通过 scheduler 自动执行（routes/console.php）。
 * 备份文件存储在 storage/app/backups/，保留最近 30 天。
 */
class DbBackup extends Command
{
    protected $signature = 'db:backup {--prune : 清理 30 天前的旧备份}';
    protected $description = 'Backup the SQLite database to storage/app/backups/';

    public function handle(): int
    {
        $dbPath = database_path('database.sqlite');
        if (! file_exists($dbPath)) {
            $this->warn('SQLite database not found at ' . $dbPath);
            return 1;
        }

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $backupFile = $backupDir . '/database_' . $timestamp . '.sqlite';

        if (copy($dbPath, $backupFile)) {
            $size = round(filesize($backupFile) / 1024, 2);
            $this->info("Backup created: {$backupFile} ({$size} KB)");
        } else {
            $this->error('Backup failed: unable to copy database file');
            return 1;
        }

        // 清理 30 天前的旧备份
        if ($this->option('prune')) {
            $cutoff = now()->subDays(30)->timestamp;
            foreach (glob($backupDir . '/database_*.sqlite') as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $this->line("Pruned old backup: " . basename($file));
                }
            }
        }

        return 0;
    }
}
