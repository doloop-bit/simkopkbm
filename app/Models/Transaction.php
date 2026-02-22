<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'student_billing_id',
        'budget_plan_id',
        'budget_plan_item_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function billing()
    {
        return $this->belongsTo(StudentBilling::class, 'student_billing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgetPlan()
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function budgetItem()
    {
        return $this->belongsTo(BudgetPlanItem::class, 'budget_plan_item_id');
    }
}
