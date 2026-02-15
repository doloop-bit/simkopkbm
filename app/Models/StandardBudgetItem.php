<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardBudgetItem extends Model
{
    protected $fillable = ['budget_category_id', 'name', 'unit', 'default_price', 'is_active'];

    public function category()
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }
}
