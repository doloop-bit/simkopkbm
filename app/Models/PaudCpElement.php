<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaudCpElement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'order',
    ];

    public function tps(): HasMany
    {
        return $this->hasMany(PaudTp::class, 'paud_cp_element_id');
    }
}
