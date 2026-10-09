@extends('layouts.boutique')
@section('title', 'Backups')
@section('content')
@php
    $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm w-full';
    $typeL = ['automatic' => 'Automatic', 'manual' => 'Manual', 'safety' => 'Safety (before restore)'];
    $statusL = ['completed' => ['Completed', 'bg-emerald-100 text-emerald-700'], 'failed' => ['Failed', 'bg-red-100 text-red-700'], 'in_progress' => ['In progress', 'bg-amber-100 text-amber-800']];
    $schedulerOk = $heartbeat && $heartbeat->gt(now()->subMinutes(10));
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
@endphp

@if ($health['warn'])
<div class="mb-4 rounded-xl border px-4 py-3 text-sm {{ $health['level'] === 'bad' ? 'bg-red-50 border-red-300 text-red-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}">{{ $health['warn'] }}</div>
@endif

<div x-data="{
        state: 'idle', msg: '', restore: null,
        async run() {
            if (!confirm('Create a backup now? This saves a full copy of your business data and uploaded files.')) return;
            this.state = 'running'; this.msg = '';
            try {
                const r = await fetch('{{ route('backups.run') }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                const d = await r.json().catch(() => ({}));
                this.state = r.ok && d.ok ? 'done' : 'failed';
                this.msg = d.message || 'The backup could not be created. Please try again.';
            } catch (e) { this.state = 'failed'; this.msg = 'Could not reach the server. The backup was not confirmed, so please check the list below before trusting it.'; }
        } }">

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Last successful backup</div>
            <div class="font-semibold">{{ $health['last'] ? $health['last']->completed_at->format('d M Y, H:i') : 'None yet' }}</div>
            @if ($health['last'])<div class="text-xs text-stone-500">{{ $health['last']->size_label }}, {{ $health['last']->completed_at->diffForHumans() }}</div>@endif</div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Next scheduled backup</div>
            <div class="font-semibold">{{ $next ? ($next->isPast() || $next->diffInMinutes(now()) < 1 ? 'Due now' : $next->format('d M Y, H:i')) : 'Automatic backups are off' }}</div></div>
        <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Backups kept</div><div class="font-semibold">{{ $count }}</div>
            <div class="text-xs text-stone-500">{{ $bytes >= 1048576 ? number_format($bytes / 1048576, 2) . ' MB' : number_format($bytes / 1024, 1) . ' KB' }} in total</div></div>
        <div class="bg-white border {{ $schedulerOk ? 'border-emerald-200' : 'border-red-300' }} rounded-xl p-4"><div class="text-xs text-stone-500">Scheduler (runs automatic backups)</div>
            <div class="font-semibold {{ $schedulerOk ? 'text-emerald-700' : 'text-red-700' }}">{{ $schedulerOk ? 'Running' : ($heartbeat ? 'Stopped' : 'Never started') }}</div>
            @if ($heartbeat)<div class="text-xs text-stone-500">last seen {{ $heartbeat->diffForHumans() }}</div>@endif</div>
    </div>

    @unless ($schedulerOk)
    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-900">
        <strong>Automatic backups will NOT run until the scheduler is started.</strong> Run this once in Command Prompt (one line), then check this page again in a minute:
        <pre class="mt-2 bg-white border border-red-200 rounded p-2 text-xs overflow-x-auto">schtasks /create /sc minute /mo 1 /tn "BoutiqueScheduler" /tr "\"{{ $phpPath }}\" \"{{ base_path('artisan') }}\" schedule:run"</pre>
        On a web host, add a cron job that runs <code>php artisan schedule:run</code> every minute instead.
    </div>
    @endunless

    @if ($me->can_do('backups.create'))
    <div class="mb-4 bg-white border border-stone-200 rounded-xl p-4">
        <button type="button" @click="run()" :disabled="state==='running'" class="rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-lg font-semibold px-8 py-4 disabled:opacity-60">
            <span x-show="state!=='running'">BACKUP NOW</span><span x-show="state==='running'" x-cloak>Creating backup... please wait</span>
        </button>
        <div x-show="state==='running'" x-cloak class="mt-3 h-2 rounded bg-stone-200 overflow-hidden"><div class="h-2 w-1/3 bg-amber-500 animate-pulse"></div></div>
        <div x-show="state==='done'" x-cloak class="mt-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 px-3 py-2 text-sm"><span x-text="msg"></span> <a href="" class="underline">Refresh the list</a></div>
        <div x-show="state==='failed'" x-cloak class="mt-3 rounded-lg bg-red-50 border border-red-200 text-red-900 px-3 py-2 text-sm"><span x-text="msg"></span> <a href="" class="underline">Refresh the list</a></div>
    </div>
    @endif

    <h2 class="font-semibold mb-2">Backup history</h2>
    <div class="bg-white border border-stone-200 rounded-xl overflow-x-auto mb-2">
    <table class="min-w-full text-sm">
        <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Contents</th><th class="px-4 py-3">Size</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Location</th><th class="px-4 py-3"></th></tr></thead>
        <tbody class="divide-y divide-stone-100">
        @forelse ($backups as $b)
            <tr>
                <td class="px-4 py-3">#{{ $b->id }}</td>
                <td class="px-4 py-3 whitespace-nowrap">{{ $b->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3">{{ $b->created_at->format('H:i:s') }}</td>
                <td class="px-4 py-3">{{ $typeL[$b->type] ?? $b->type }}@if ($b->user)<div class="text-xs text-stone-500">{{ $b->user->name }}</div>@endif</td>
                <td class="px-4 py-3">{{ $b->scope === 'full' ? 'Data + files' : 'Data only' }}</td>
                <td class="px-4 py-3">{{ $b->size_label }}</td>
                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs {{ $statusL[$b->status][1] ?? '' }}">{{ $statusL[$b->status][0] ?? $b->status }}</span>
                    @if ($b->status === 'failed' && $b->error)<div class="text-xs text-red-700 mt-1 max-w-xs">{{ $b->error }}</div>@endif</td>
                <td class="px-4 py-3 text-xs text-stone-500">{{ $b->file_name ?? '-' }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-right space-x-3">
                    @if ($b->status === 'completed')
                        <a class="underline" href="{{ route('backups.download', $b) }}">Download</a>
                        @if ($me->can_do('backups.restore'))<button type="button" class="underline text-red-700" @click="restore = { id: {{ $b->id }}, date: '{{ $b->completed_at->format('d M Y, H:i') }}', url: '{{ route('backups.restore', $b) }}' }">Restore</button>@endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="px-4 py-10 text-center text-stone-500">No backups yet. Click BACKUP NOW.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="mb-6">{{ $backups->links() }}</div>
    <p class="mb-6 text-xs text-stone-500">Stored in the private folder <code>{{ $location }}</code>, which is not reachable from the internet. Backups contain sensitive business data and staff password hashes, so keep downloaded copies safe. Backup files are not encrypted by the system.</p>

    @if ($me->can_do('backups.edit'))
    <form method="POST" action="{{ route('backups.schedule') }}" class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-4 gap-3 mb-6">
        @csrf
        <h2 class="font-semibold sm:col-span-4">Automatic backup schedule</h2>
        <label class="flex items-center gap-2 text-sm sm:col-span-4"><input type="checkbox" name="backup_enabled" value="1" @checked($s('backup_enabled', '1') === '1')> Automatic backups are on</label>
        <label class="text-xs">Daily at (data)<input type="time" name="backup_daily_time" value="{{ $s('backup_daily_time', '02:00') }}" class="{{ $inp }}"></label>
        <label class="text-xs">Weekly full backup on<select name="backup_weekly_day" class="{{ $inp }}">@foreach ($days as $d)<option value="{{ $d }}" @selected($s('backup_weekly_day', 'sunday') === $d)>{{ ucfirst($d) }}</option>@endforeach</select></label>
        <label class="text-xs">Keep automatic backups for (days)<input type="number" min="7" max="365" name="backup_retention_days" value="{{ $s('backup_retention_days', 30) }}" class="{{ $inp }}"></label>
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm self-end">Save schedule</button>
        <p class="text-xs text-stone-500 sm:col-span-4">The daily backup saves the database. The weekly backup also saves uploaded files (product photos, logo, expense receipts). Manual backups are never deleted automatically.</p>
    </form>
    @endif

    {{-- Restore dialog --}}
    <div x-show="restore" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="restore=null">
        <div class="bg-white rounded-xl max-w-md w-full p-5 space-y-3" @click.outside="restore=null">
            <h3 class="text-lg font-semibold text-red-700">Restore backup #<span x-text="restore?.id"></span></h3>
            <p class="text-sm">Backup taken: <strong x-text="restore?.date"></strong></p>
            <p class="text-sm rounded-lg bg-red-50 border border-red-200 text-red-900 p-3">Restoring this backup will replace the current business data with the selected backup. Anything done since that backup will be removed from the system. A safety backup of your current data is made first, so it can be recovered.</p>
            <form method="POST" :action="restore?.url" class="space-y-2">
                @csrf
                <label class="text-xs block">Type RESTORE to confirm<input name="confirm" required autocomplete="off" class="{{ $inp }}"></label>
                <label class="text-xs block">Your password<input name="password" type="password" required autocomplete="current-password" class="{{ $inp }}"></label>
                <div class="flex gap-2 pt-1">
                    <button class="rounded-lg bg-red-700 text-white px-4 py-2 text-sm">Restore now</button>
                    <button type="button" @click="restore=null" class="rounded-lg border border-stone-300 px-4 py-2 text-sm">Cancel</button>
                </div>
                <p class="text-xs text-stone-500">This can take a minute. Do not close the page. You will be logged out afterwards.</p>
            </form>
        </div>
    </div>
</div>
@endsection
