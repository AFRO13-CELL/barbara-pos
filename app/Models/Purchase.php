<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];
    protected $casts = ['purchase_date' => 'date', 'received_at' => 'datetime'];
    public function supplier() { return $this->belongsTo(Supplier::class)->withTrashed(); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function payments() { return $this->hasMany(PurchasePayment::class); }
}
