<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'base_salary',
        'effective_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'integer',
            'effective_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(SalaryTemplateComponent::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(SalaryTemplateComponent::class)->where('type', 'allowance');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(SalaryTemplateComponent::class)->where('type', 'deduction');
    }

    public function getTotalAllowancesAttribute(): int
    {
        return (int) $this->allowances()->sum('amount');
    }

    public function getTotalDeductionsAttribute(): int
    {
        return (int) $this->deductions()->sum('amount');
    }

    public function getNetSalaryAttribute(): int
    {
        return $this->base_salary + $this->total_allowances - $this->total_deductions;
    }
}
