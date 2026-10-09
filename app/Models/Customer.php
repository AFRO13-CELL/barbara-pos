<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = ['name', 'phone', 'email', 'address', 'notes'];
    public function sales() { return $this->hasMany(Sale::class); }
}
