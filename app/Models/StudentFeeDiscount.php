<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFeeDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_category_id',
        'name',
        'discount_type',
        'amount',
        'frequency',
        'is_applied',
    ];

    protected function casts(): array
    {
        return [
            'is_applied' => 'boolean',
            'amount' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }
}
