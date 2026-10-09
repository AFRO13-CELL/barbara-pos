<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $guarded = [];
    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }

    public function getSizeLabelAttribute(): string
    {
        $b = (int) $this->size_bytes;
        if ($b <= 0) return '-';
        return $b >= 1048576 ? number_format($b / 1048576, 2) . ' MB' : number_format($b / 1024, 1) . ' KB';
    }
}
