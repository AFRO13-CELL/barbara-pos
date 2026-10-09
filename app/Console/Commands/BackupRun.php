<?php
namespace App\Console\Commands;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    protected $signature = 'boutique:backup {--scope=full : full or database}';
    protected $description = 'Creates a backup right now (handy for testing).';

    public function handle(): int
    {
        $b = BackupService::run('manual', $this->option('scope') === 'database' ? 'database' : 'full');
        $b->status === 'completed' ? $this->info("Backup #{$b->id} created: {$b->file_name} ({$b->size_label})") : $this->error("Backup #{$b->id} FAILED: {$b->error}");
        return $b->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
