<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'default_amount', 'level_id', 'billing_type'];

    public function billings()
    {
        return $this->hasMany(StudentBilling::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
