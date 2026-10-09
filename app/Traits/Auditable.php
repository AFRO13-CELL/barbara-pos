<?php
namespace App\Traits;
use App\Models\AuditLog;

// Add `use Auditable;` to a model to log create/update/delete automatically.
// Passwords and tokens are never written to the audit log.
trait Auditable
{
    private static function auditClean(array $a): array
    {
        return array_diff_key($a, array_flip(['password', 'remember_token', 'updated_at']));
    }

    protected static function bootAuditable(): void
    {
        static::created(fn ($m) => AuditLog::record('created', $m, class_basename($m) . ' created', null, self::auditClean($m->getAttributes())));
        static::updated(function ($m) {
            $changes = self::auditClean($m->getChanges());
            $passwordChanged = $m->wasChanged('password');
            if (!$changes && !$passwordChanged) return;
            $old = collect($changes)->map(fn ($v, $k) => $m->getOriginal($k))->all();
            $desc = class_basename($m) . ' updated' . ($passwordChanged ? ' (password changed)' : '');
            AuditLog::record('updated', $m, $desc, $old, $changes);
        });
        static::deleted(fn ($m) => AuditLog::record('deleted', $m, class_basename($m) . ' deleted', self::auditClean($m->getOriginal())));
    }
}
