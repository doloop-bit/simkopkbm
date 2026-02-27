<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'managed_level_id',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function latestProfile()
    {
        return $this->hasOne(Profile::class)->latestOfMany();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class)->latestOfMany();
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class, 'student_id');
    }

    public function feeDiscounts()
    {
        return $this->hasMany(StudentFeeDiscount::class, 'student_id');
    }

    public function studentProfile()
    {
        return $this->hasOneThrough(StudentProfile::class, Profile::class, 'user_id', 'id', 'id', 'profileable_id')
            ->where('profiles.profileable_type', StudentProfile::class);
    }

    // Teacher Assignment Relationships
    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    public function assignedClassrooms()
    {
        return $this->belongsToMany(Classroom::class, 'teacher_assignments', 'teacher_id', 'classroom_id')
            ->distinct();
    }

    public function assignedSubjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments', 'teacher_id', 'subject_id')
            ->whereNotNull('teacher_assignments.subject_id')
            ->distinct();
    }

    // Teacher Access Control Methods
    public function hasAccessToClassroom(int $classroomId): bool
    {
        if ($this->role !== 'guru') {
            return true; // Admins have access to everything
        }

        return $this->teacherAssignments()
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    public function hasAccessToSubject(int $subjectId): bool
    {
        if ($this->role !== 'guru') {
            return true; // Admins have access to everything
        }

        // Direct subject assignment
        if ($this->teacherAssignments()->where('subject_id', $subjectId)->exists()) {
            return true;
        }

        // Access via homeroom (class_teacher) assignment
        $subject = Subject::find($subjectId);
        if (! $subject) {
            return false;
        }

        $classroomIdsAsHomeroom = $this->teacherAssignments()
            ->whereIn('type', ['class_teacher', 'homeroom'])
            ->pluck('classroom_id');

        return Classroom::whereIn('id', $classroomIdsAsHomeroom)
            ->where('level_id', $subject->level_id)
            ->exists();
    }

    public function getAssignedClassroomIds(): array
    {
        return $this->teacherAssignments()
            ->pluck('classroom_id')
            ->unique()
            ->values()
            ->toArray();
    }

    public function getAssignedSubjectIds(): array
    {
        // 1. Subjects directly assigned
        $ids = $this->teacherAssignments()
            ->whereNotNull('subject_id')
            ->pluck('subject_id')
            ->toArray();

        // 2. All subjects for classrooms where teacher is homeroom (class_teacher)
        $classroomIdsAsHomeroom = $this->teacherAssignments()
            ->whereIn('type', ['class_teacher', 'homeroom'])
            ->pluck('classroom_id');

        if ($classroomIdsAsHomeroom->isNotEmpty()) {
            $levelIds = Classroom::whereIn('id', $classroomIdsAsHomeroom)->pluck('level_id');
            $homeroomSubjectIds = Subject::whereIn('level_id', $levelIds)->pluck('id')->toArray();
            $ids = array_merge($ids, $homeroomSubjectIds);
        }

        return array_values(array_unique($ids));
    }

    public function teachesPaudLevel(): bool
    {
        if ($this->role !== 'guru') {
            return false;
        }

        return $this->teacherAssignments()
            ->whereHas('classroom.level', function ($q) {
                $q->where('education_level', 'PAUD');
            })
            ->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // Financial & Level Management Roles
    public function managedLevel()
    {
        return $this->belongsTo(Level::class, 'managed_level_id');
    }

    public function isTreasurer(): bool
    {
        return $this->role === 'bendahara';
    }

    public function isHeadmaster(): bool
    {
        return $this->role === 'kepsek';
    }

    public function isYayasan(): bool
    {
        return $this->role === 'yayasan';
    }

    public function canManageLevel(int $levelId): bool
    {
        if ($this->isAdmin() || $this->isYayasan()) {
            return true;
        }

        return $this->managed_level_id === $levelId;
    }
}
