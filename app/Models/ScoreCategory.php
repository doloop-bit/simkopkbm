<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'weight'];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
