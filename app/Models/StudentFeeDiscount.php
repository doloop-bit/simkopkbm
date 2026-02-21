<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeeDiscount extends Model
{
    protected $fillable = [
        'student_id',
        'fee_category_id',
        'name',
        'discount_type',
        'amount',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }
}
