<?php

namespace App\Models;

use App\Models\Concerns\HasTransactionRelationships;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasTransactionRelationships;

    protected $fillable = [
        'type',
        'student_billing_id',
        'fee_category_id',
        'budget_plan_id',
        'budget_plan_item_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'attachment' => 'array',
        ];
    }
}
