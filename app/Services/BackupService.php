<?php
namespace App\Services;
use App\Models\{AuditLog, Backup, Notification, Setting, User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Storage};
use ZipArchive;

class BackupService
{
    /** Temporary tables are not worth saving. `backups` is left alone so the history survives a restore. */
    public const EXCLUDE = ['backups', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];
    public const DELIM = "\n-- ;;\n";

    public static function dir(): string
    {
        $d = Storage::disk('local')->path('backups');          // private storage, never inside the public web folder
        if (!is_dir($d)) mkdir($d, 0750, true);
        return $d;
    }

    public static function pathFor(Backup $b): ?string
    {
        return $b->file_name ? self::dir() . DIRECTORY_SEPARATOR . $b->file_name : null;
    }

    /** Create a backup. The record is only marked "completed" after the file has been written AND checked. */
    public static function run(string $type, string $scope, ?User $user = null): Backup
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $b = Backup::create(['type' => $type, 'scope' => $scope, 'status' => 'in_progress', 'user_id' => $user?->id, 'started_at' => now()]);
        $tmp = null; $path = null;
        try {
            if (!class_exists(ZipArchive::class)) throw new \RuntimeException('The PHP "zip" extension is switched off, so backup files cannot be created.');
            $name = sprintf('backup-%s-%s-%d.zip', now()->format('Ymd-His'), $type, $b->id);
            $path = self::dir() . DIRECTORY_SEPARATOR . $name;
            $tmp = tempnam(self::dir(), 'sql');

            $info = self::dumpDatabase($tmp);
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Could not create the backup file. Check that the storage folder is writable.');
            $zip->addFile($tmp, 'database.sql');
            $files = 0;
            if ($scope === 'full') {
                $files += self::addFiles($zip, Storage::disk('public')->path(''), 'files/public');
                $files += self::addFiles($zip, Storage::disk('local')->path('expenses'), 'files/expenses');
            }
            $zip->addFromString('manifest.json', json_encode([
                'app' => "Barbara's Boutique", 'created_at' => now()->toIso8601String(), 'type' => $type, 'scope' => $scope,
                'tables' => $info['rows'], 'files' => $files, 'laravel' => app()->version(),
            ], JSON_PRETTY_PRINT));
            if (!$zip->close()) throw new \RuntimeException('Could not finish writing the backup file (is the disk full?).');
            @unlink($tmp);

            self::verify($path);
            $b->update(['status' => 'completed', 'file_name' => $name, 'size_bytes' => filesize($path), 'checksum' => hash_file('sha256', $path),
                        'tables_count' => count($info['rows']), 'completed_at' => now()]);
            AuditLog::record('backup_created', $b, ($user?->name ?? 'System') . " created a {$type} backup #{$b->id} ({$b->size_label})");
            if ($type !== 'safety') {
                Notification::create(['type' => 'backup_completed', 'title' => 'Backup completed', 'message' => ucfirst($type) . " backup #{$b->id} finished ({$b->size_label}).", 'url' => route('backups.index')]);
            }
        } catch (\Throwable $e) {
            if ($tmp && is_file($tmp)) @unlink($tmp);
            if ($path && is_file($path)) @unlink($path);
            $b->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'completed_at' => now()]);
            AuditLog::record('backup_failed', $b, "Backup #{$b->id} failed: " . mb_substr($e->getMessage(), 0, 200));
            Notification::create(['type' => 'backup_failed', 'title' => 'Backup FAILED', 'message' => "Backup #{$b->id} failed: " . mb_substr($e->getMessage(), 0, 200), 'url' => route('backups.index')]);
        }
        return $b->fresh();
    }

    /** Writes every business table as SQL. Statements are separated by a marker line so they can be replayed safely. */
    private static function dumpDatabase(string $file): array
    {
        $pdo = DB::connection()->getPdo();
        $h = fopen($file, 'wb');
        if (!$h) throw new \RuntimeException('Could not write a temporary file in the backups folder.');
        $w = function (string $s) use ($h) { fwrite($h, $s . self::DELIM); };
        fwrite($h, "-- Barbara's Boutique database backup " . now()->toDateTimeString() . self::DELIM);
        $w('SET FOREIGN_KEY_CHECKS=0');
        $w('SET NAMES utf8mb4');
        $tables = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($r) => array_values((array) $r)[0])->reject(fn ($t) => in_array($t, self::EXCLUDE, true))->values();
        $rows = [];
        foreach ($tables as $t) {
            $w("DROP TABLE IF EXISTS `$t`");
            $create = (array) DB::select("SHOW CREATE TABLE `$t`")[0];
            $w($create['Create Table']);
            $n = 0; $cols = null; $buf = [];
            foreach (DB::table($t)->cursor() as $row) {
                $row = (array) $row;
                $cols ??= '`' . implode('`,`', array_keys($row)) . '`';
                $buf[] = '(' . implode(',', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                $n++;
                if (count($buf) >= 200) { $w("INSERT INTO `$t` ($cols) VALUES " . implode(',', $buf)); $buf = []; }
            }
            if ($buf) $w("INSERT INTO `$t` ($cols) VALUES " . implode(',', $buf));
            $rows[$t] = $n;
        }
        $w('SET FOREIGN_KEY_CHECKS=1');
        fwrite($h, "-- END OF BACKUP\n");
        fclose($h);
        return ['rows' => $rows];
    }

    private static function addFiles(ZipArchive $zip, string $base, string $prefix): int
    {
        if (!is_dir($base)) return 0;
        $n = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(rtrim($base, '/\\')) + 1));
            $zip->addFile($f->getPathname(), $prefix . '/' . $rel);
            $n++;
        }
        return $n;
    }

    /** Re-opens the finished file and checks it really contains a complete database dump. */
    public static function verify(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new \RuntimeException('The backup file could not be re-opened, so it cannot be trusted.');
        if ($zip->locateName('manifest.json') === false) { $zip->close(); throw new \RuntimeException('The backup file is missing its manifest.'); }
        $s = $zip->getStream('database.sql');
        if (!$s) { $zip->close(); throw new \RuntimeException('The backup file has no database in it.'); }
        $tail = '';
        while (!feof($s)) { $tail = substr($tail . fread($s, 1048576), -200); }
        fclose($s); $zip->close();
        if (!str_contains($tail, '-- END OF BACKUP')) throw new \RuntimeException('The database dump inside the backup is incomplete.');
    }

    private static function extractSql(string $zipPath, string $dest): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) throw new \RuntimeException('Could not open the backup file.');
        $in = $zip->getStream('database.sql');
        $out = fopen($dest, 'wb');
        if (!$in || !$out) throw new \RuntimeException('Could not unpack the backup.');
        stream_copy_to_stream($in, $out);
        fclose($in); fclose($out); $zip->close();
    }

    private static function applySql(string $file): void
    {
        $h = fopen($file, 'rb');
        $buffer = '';
        try {
            while (!feof($h) || $buffer !== '') {
                if (!feof($h)) $buffer .= fread($h, 1048576);
                while (($pos = strpos($buffer, self::DELIM)) !== false) {
                    $stmt = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + strlen(self::DELIM));
                    if ($stmt !== '' && !str_starts_with($stmt, '--')) DB::unprepared($stmt);
                }
                if (feof($h)) break;
            }
        } finally {
            fclose($h);
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private static function restoreFiles(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) return;
        $targets = ['files/public/' => Storage::disk('public')->path(''), 'files/expenses/' => Storage::disk('local')->path('expenses')];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            foreach ($targets as $prefix => $base) {
                if (!str_starts_with($name, $prefix) || str_ends_with($name, '/')) continue;
                $rel = substr($name, strlen($prefix));
                if ($rel === '' || str_contains($rel, '..')) continue;     // never write outside the target folder
                $dest = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                $in = $zip->getStream($name);
                if ($in) { $out = fopen($dest, 'wb'); stream_copy_to_stream($in, $out); fclose($in); fclose($out); }
            }
        }
        $zip->close();
    }

    /** Restore: safety backup first, then replace the data. If anything fails, the safety backup is put back. */
    public static function restore(Backup $b, User $user): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $path = self::pathFor($b);
        if ($b->status !== 'completed' || !$path || !is_file($path)) throw new \RuntimeException('That backup file is missing, so it cannot be restored.');
        if ($b->checksum && !hash_equals($b->checksum, hash_file('sha256', $path))) {
            throw new \RuntimeException('That backup file has been changed or damaged (its checksum does not match). Nothing was restored.');
        }
        self::verify($path);

        $safety = self::run('safety', 'full', $user);
        if ($safety->status !== 'completed') throw new \RuntimeException('A safety backup of your current data could not be made (' . $safety->error . '), so nothing was changed.');

        $tmp = tempnam(self::dir(), 'rst');
        $err = null; $rolledBack = false;
        try {
            self::extractSql($path, $tmp);
            self::applySql($tmp);
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            $rb = tempnam(self::dir(), 'rb');
            try { self::extractSql(self::pathFor($safety), $rb); self::applySql($rb); $rolledBack = true; } catch (\Throwable $e2) { /* reported below */ }
            @unlink($rb);
        } finally {
            @unlink($tmp);
        }
        if ($err !== null) {
            throw new \RuntimeException($rolledBack
                ? "The restore failed ({$err}). Your previous data was put back from safety backup #{$safety->id}."
                : "The restore failed ({$err}) and the automatic roll-back also failed. Restore safety backup #{$safety->id} from the list as soon as possible.");
        }
        self::restoreFiles($path);
        cache()->flush();
        AuditLog::create(['user_id' => User::whereKey($user->id)->exists() ? $user->id : null, 'action' => 'backup_restored',
            'auditable_type' => Backup::class, 'auditable_id' => $b->id,
            'description' => "{$user->name} restored backup #{$b->id} from " . $b->completed_at?->format('d M Y H:i') . " (safety backup #{$safety->id})", 'ip_address' => request()?->ip()]);
    }

    /** Removes old automatic/safety backups. Always keeps the 3 newest good ones. Manual backups are never deleted automatically. */
    public static function prune(): void
    {
        $days = max(7, (int) Setting::get('backup_retention_days', 30));
        $keep = Backup::where('status', 'completed')->latest('id')->limit(3)->pluck('id');
        foreach (Backup::whereIn('type', ['automatic', 'safety'])->where('created_at', '<', now()->subDays($days))->whereNotIn('id', $keep)->get() as $old) {
            if ($old->file_name && is_file(self::pathFor($old))) @unlink(self::pathFor($old));
            $old->delete();
        }
    }

    public static function health(): array
    {
        $last = Backup::where('status', 'completed')->latest('completed_at')->first();
        $recent = Backup::where('type', 'automatic')->latest('id')->limit(3)->get();
        $warn = null; $level = 'warn';
        if ($recent->count() === 3 && $recent->every(fn ($b) => $b->status === 'failed')) {
            $warn = 'Automatic backups have failed 3 times in a row. Open Backups to see why.'; $level = 'bad';
        } elseif (!$last) {
            $warn = 'No backup has been made yet. Open Backups and click Backup Now.';
        } elseif ((bool) Setting::get('backup_enabled', '1') && $last->completed_at->lt(now()->subHours(48))) {
            $warn = 'The last successful backup was ' . $last->completed_at->diffForHumans() . '. Check that the scheduler is running.';
        }
        return compact('last', 'warn', 'level');
    }

    public static function next(): ?Carbon
    {
        if (!(bool) Setting::get('backup_enabled', '1')) return null;
        $parts = array_map('intval', explode(':', (string) Setting::get('backup_daily_time', '02:00')) + [0, 0]);
        $t = now()->setTime($parts[0], $parts[1], 0);
        $done = Backup::where('type', 'automatic')->where('status', 'completed')->whereDate('created_at', today())->exists();
        if ($t->isPast() && !$done) return now();
        return $t->isFuture() ? $t : $t->addDay();
    }
}
