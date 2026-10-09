<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function variation() { return $this->belongsTo(ProductVariation::class, 'product_variation_id')->withTrashed(); }
}
