<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectTp extends Model
{
    protected $fillable = ['subject_id', 'code', 'description'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
