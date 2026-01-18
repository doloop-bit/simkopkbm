<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_profile_id',
        'name',
        'position',
        'photo_path',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function schoolProfile()
    {
        return $this->belongsTo(SchoolProfile::class);
    }
}
