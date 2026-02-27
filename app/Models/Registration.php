<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'name',
        'nik',
        'nisn',
        'pob',
        'dob',
        'gender',
        'phone',
        'email',
        'address',
        'province_id',
        'province_name',
        'regency_id',
        'regency_name',
        'district_id',
        'district_name',
        'village_id',
        'village_name',
        'father_name',
        'mother_name',
        'guardian_name',
        'guardian_phone',
        'nik_ayah',
        'nik_ibu',
        'no_kk',
        'no_akta',
        'birth_order',
        'total_siblings',
        'previous_school',
        'photo',
        'preferred_level_id',
        'academic_year_id',
        'status',
        'notes',
        'enrolled_at',
        'enrolled_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'enrolled_at' => 'datetime',
            'birth_order' => 'integer',
            'total_siblings' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────

    public function preferredLevel()
    {
        return $this->belongsTo(Level::class, 'preferred_level_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrolledByUser()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    // ─── Scopes ───────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeEnrolled(Builder $query): Builder
    {
        return $query->where('status', 'enrolled');
    }

    // ─── Helpers ──────────────────────────────────

    /**
     * Generate a unique registration number: REG-{YEAR}-{SEQUENCE}
     */
    public static function generateRegistrationNumber(): string
    {
        $year = date('Y');
        $prefix = "REG-{$year}-";

        $lastNumber = static::where('registration_number', 'like', "{$prefix}%")
            ->orderByDesc('registration_number')
            ->value('registration_number');

        if ($lastNumber) {
            $sequence = (int) str_replace($prefix, '', $lastNumber) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village_name ? "Kel. {$this->village_name}" : null,
            $this->district_name ? "Kec. {$this->district_name}" : null,
            $this->regency_name,
            $this->province_name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak',
            'enrolled' => 'Terdaftar',
            default => $this->status,
        };
    }

    /**
     * Get the status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'accepted' => 'blue',
            'rejected' => 'red',
            'enrolled' => 'green',
            default => 'gray',
        };
    }
}
