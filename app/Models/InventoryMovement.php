<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    public function variation() { return $this->belongsTo(ProductVariation::class, 'product_variation_id')->withTrashed(); }
    public function user() { return $this->belongsTo(User::class); }
}
