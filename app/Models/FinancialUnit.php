<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialUnit extends Model
{
    /** @use HasFactory<\Database\Factories\FinancialUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    /**
     * Get the levels associated with this financial unit.
     */
    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    /**
     * Get the transactions recorded under this financial unit.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get users (e.g. bendahara) assigned to manage this financial unit.
     */
    public function managers(): HasMany
    {
        return $this->hasMany(User::class, 'managed_financial_unit_id');
    }
}
