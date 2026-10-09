<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = ['product_id','sku','barcode','size','color','material','style','gender','item_condition',
        'cost_price','selling_price','discount_price','stock_qty','min_stock','image_path'];
    protected $casts = ['cost_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'discount_price' => 'decimal:2'];

    public function product() { return $this->belongsTo(Product::class); }
    public function movements() { return $this->hasMany(InventoryMovement::class); }

    public function getStatusAttribute(): string
    {
        if ($this->stock_qty <= 0) return 'out';
        if ($this->stock_qty <= $this->min_stock) return 'low';
        return 'in';
    }
    public function getOptionsLabelAttribute(): string
    {
        return collect([$this->size, $this->color])->filter()->implode(' / ') ?: 'Standard';
    }
}
