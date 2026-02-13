<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectTp extends Model
{
    protected $fillable = ['learning_achievement_id', 'code', 'description'];

    /**
     * Get the learning achievement (CP) that this TP belongs to.
     */
    public function learningAchievement()
    {
        return $this->belongsTo(LearningAchievement::class);
    }

    /**
     * Get the subject through the learning achievement.
     */
    public function subject()
    {
        return $this->hasOneThrough(
            Subject::class,
            LearningAchievement::class,
            'id',            // FK on learning_achievements
            'id',            // FK on subjects
            'learning_achievement_id', // local key on subject_tps
            'subject_id'     // FK on learning_achievements
        );
    }
}
