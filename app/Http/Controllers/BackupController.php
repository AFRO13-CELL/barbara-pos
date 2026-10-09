<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog, Backup, Setting};
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, Hash};

class BackupController extends Controller
{
    public function index()
    {
        $hb = Cache::get('scheduler_heartbeat');
        return view('backups.index', [
            'backups' => Backup::with('user')->latest('id')->paginate(20),
            'health' => BackupService::health(), 'next' => BackupService::next(),
            'count' => Backup::where('status', 'completed')->count(), 'bytes' => (int) Backup::where('status', 'completed')->sum('size_bytes'),
            'location' => BackupService::dir(), 'heartbeat' => $hb ? \Illuminate\Support\Carbon::parse($hb) : null,
            's' => fn ($k, $d = null) => Setting::get($k, $d), 'phpPath' => PHP_BINARY,
        ]);
    }

    public function run(Request $request)
    {
        $b = BackupService::run('manual', 'full', $request->user());
        if ($b->status === 'completed') {
            return response()->json(['ok' => true, 'message' => "Backup #{$b->id} completed at " . $b->completed_at->format('d M Y, H:i:s') . " ({$b->size_label}).", 'id' => $b->id]);
        }
        return response()->json(['ok' => false, 'message' => 'The backup FAILED: ' . ($b->error ?: 'unknown error') . ' Nothing was saved.'], 500);
    }

    public function download(Request $request, Backup $backup)
    {
        $path = BackupService::pathFor($backup);
        abort_unless($backup->status === 'completed' && $path && is_file($path), 404, 'That backup file no longer exists.');
        AuditLog::record('backup_downloaded', $backup, "{$request->user()->name} downloaded backup #{$backup->id}");
        return response()->download($path, $backup->file_name);
    }

    public function schedule(Request $request)
    {
        $d = $request->validate([
            'backup_daily_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'backup_weekly_day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'backup_retention_days' => 'required|integer|min:7|max:365',
        ]);
        $d['backup_enabled'] = $request->boolean('backup_enabled') ? '1' : '0';
        foreach ($d as $k => $v) Setting::set($k, (string) $v);
        AuditLog::record('backup_schedule_changed', null, "{$request->user()->name} changed the backup schedule", null, $d);
        return back()->with('status', 'Backup schedule saved.');
    }

    public function restore(Request $request, Backup $backup)
    {
        $request->validate(['confirm' => 'required|in:RESTORE', 'password' => 'required|string']);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) return back()->withErrors(['password' => 'That password is not correct. Nothing was restored.']);
        try {
            BackupService::restore($backup, $user);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['restore' => $e->getMessage()]);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('status', 'Backup restored successfully. Please log in again.');
    }
}
