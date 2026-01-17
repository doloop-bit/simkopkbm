<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'vision',
        'mission',
        'history',
        'operating_hours',
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'twitter_url',
        'latitude',
        'longitude',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function staffMembers()
    {
        return $this->hasMany(StaffMember::class)->orderBy('order');
    }

    public function facilities()
    {
        return $this->hasMany(Facility::class)->orderBy('order');
    }

    /**
     * Get the active school profile.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Ensure only one active profile exists at a time.
     */
    protected static function booted(): void
    {
        static::creating(function ($profile) {
            if ($profile->is_active) {
                static::where('is_active', true)->update(['is_active' => false]);
            }
        });

        static::updating(function ($profile) {
            if ($profile->is_active && $profile->isDirty('is_active')) {
                static::where('id', '!=', $profile->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }
}
