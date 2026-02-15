<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'is_active'];

    public function items()
    {
        return $this->hasMany(StandardBudgetItem::class);
    }
}
