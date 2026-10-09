<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = ['category_id', 'brand_id', 'name', 'description', 'image_path'];
    public function category() { return $this->belongsTo(Category::class)->withTrashed(); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function variations() { return $this->hasMany(ProductVariation::class)->orderBy('id'); }
}
