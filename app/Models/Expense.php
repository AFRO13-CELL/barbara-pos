<?php
namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes, Auditable;
    public const CATEGORIES = ['Rent', 'Electricity', 'Water', 'Internet', 'Transport', 'Salaries', 'Marketing', 'Packaging', 'Repairs', 'Maintenance', 'Supplier payments', 'Other'];
    protected $fillable = ['description', 'category', 'amount', 'expense_date', 'payment_method', 'user_id', 'attachment_path', 'notes'];
    protected $casts = ['expense_date' => 'date'];
    public function user() { return $this->belongsTo(User::class)->withTrashed(); }
}
