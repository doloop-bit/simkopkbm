<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'default_amount', 'level_id'];

    public function billings()
    {
        return $this->hasMany(StudentBilling::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
