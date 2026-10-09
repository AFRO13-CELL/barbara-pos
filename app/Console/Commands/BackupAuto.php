<?php
namespace App\Console\Commands;
use App\Models\{Backup, Setting};
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class BackupAuto extends Command
{
    protected $signature = 'boutique:backup-auto';
    protected $description = 'Runs the daily (database) and weekly (full) backups when they are due.';

    public function handle(): int
    {
        Cache::put('scheduler_heartbeat', now()->toIso8601String(), 86400);     // lets the Backups page show that the scheduler is alive
        if (!(bool) Setting::get('backup_enabled', '1')) return self::SUCCESS;

        $parts = array_map('intval', explode(':', (string) Setting::get('backup_daily_time', '02:00')) + [0, 0]);
        if (now()->lt(now()->setTime($parts[0], $parts[1], 0))) return self::SUCCESS;                 // not time yet

        if (Backup::where('type', 'automatic')->where('status', 'completed')->whereDate('created_at', today())->exists()) return self::SUCCESS;
        $lastTry = Backup::where('type', 'automatic')->latest('id')->first();
        if ($lastTry && $lastTry->created_at->gt(now()->subMinutes(30))) return self::SUCCESS;       // after a failure, wait before retrying

        $weeklyDay = strtolower(now()->englishDayOfWeek) === strtolower((string) Setting::get('backup_weekly_day', 'sunday'));
        $fullRecently = Backup::where('status', 'completed')->where('scope', 'full')->where('created_at', '>', now()->subDays(7))->exists();
        $scope = ($weeklyDay || !$fullRecently) ? 'full' : 'database';

        $b = BackupService::run('automatic', $scope);
        $this->info("Backup #{$b->id} {$b->status}" . ($b->error ? ": {$b->error}" : ''));
        if ($b->status === 'completed') BackupService::prune();
        return $b->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
