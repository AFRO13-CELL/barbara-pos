<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];
    public function sale() { return $this->belongsTo(Sale::class); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
}
