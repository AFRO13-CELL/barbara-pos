<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = ['name', 'parent_id', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function products() { return $this->hasMany(Product::class); }
}
