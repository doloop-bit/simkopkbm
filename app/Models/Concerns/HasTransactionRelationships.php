<?php

namespace App\Models\Concerns;

use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\FeeCategory;
use App\Models\StudentBilling;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTransactionRelationships
{
    public function billing(): BelongsTo
    {
        return $this->belongsTo(StudentBilling::class, 'student_billing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetPlanItem::class, 'budget_plan_item_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }
}
