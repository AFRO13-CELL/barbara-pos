<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $table = 'returns';
    protected $guarded = [];
    public function sale() { return $this->belongsTo(Sale::class); }
    public function customer() { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
    public function items() { return $this->hasMany(ReturnItem::class, 'return_id'); }
}
