<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, Auditable;
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'notes'];
    public function purchases() { return $this->hasMany(Purchase::class); }
}
