<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];
    protected $casts = ['due_date' => 'date'];
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function customer() { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
}
