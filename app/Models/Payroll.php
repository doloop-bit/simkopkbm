<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academic_year_id',
        'month',
        'base_salary',
        'components',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'integer',
            'total_allowances' => 'integer',
            'total_deductions' => 'integer',
            'net_salary' => 'integer',
            'components' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * @return array{allowances: array<int, array{name: string, amount: int, description: string|null}>, deductions: array<int, array{name: string, amount: int, description: string|null}>}
     */
    public function getGroupedComponents(): array
    {
        $components = $this->components ?? [];

        return [
            'allowances' => array_values(array_filter($components, fn ($c) => ($c['type'] ?? '') === 'allowance')),
            'deductions' => array_values(array_filter($components, fn ($c) => ($c['type'] ?? '') === 'deduction')),
        ];
    }

    /**
     * Convert net salary to Indonesian words (Terbilang)
     */
    public function getAmountInWords(): string
    {
        $amount = (int) $this->net_salary;
        if ($amount === 0) {
            return 'nol';
        }

        $units = ['', 'ribu', 'juta', 'miliar', 'triliun'];
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        $result = '';

        $terbilang = function ($n) use (&$terbilang, $words) {
            if ($n < 12) {
                return $words[$n];
            } elseif ($n < 20) {
                return $terbilang($n - 10).' belas';
            } elseif ($n < 100) {
                return $terbilang((int) ($n / 10)).' puluh '.$terbilang($n % 10);
            } elseif ($n < 200) {
                return 'seratus '.$terbilang($n - 100);
            } elseif ($n < 1000) {
                return $terbilang((int) ($n / 100)).' ratus '.$terbilang($n % 100);
            } elseif ($n < 2000) {
                return 'seribu '.$terbilang($n - 1000);
            } elseif ($n < 1000000) {
                return $terbilang((int) ($n / 1000)).' ribu '.$terbilang($n % 1000);
            } elseif ($n < 1000000000) {
                return $terbilang((int) ($n / 1000000)).' juta '.$terbilang($n % 1000000);
            } elseif ($n < 1000000000000) {
                return $terbilang((int) ($n / 1000000000)).' miliar '.$terbilang($n % 1000000000);
            } else {
                return $terbilang((int) ($n / 1000000000000)).' triliun '.$terbilang($n % 1000000000000);
            }
        };

        $result = $terbilang($amount);

        // Clean up double spaces and trim
        return trim(preg_replace('/\s+/', ' ', $result));
    }
}
