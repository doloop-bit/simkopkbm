<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetPlanItem extends Model
{
    protected $fillable = [
        'budget_plan_id', 'standard_budget_item_id', 'name',
        'quantity', 'unit', 'amount', 'total',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function standardItem()
    {
        return $this->belongsTo(StandardBudgetItem::class, 'standard_budget_item_id');
    }
}
