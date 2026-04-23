<?php

declare(strict_types=1);

namespace App\Traits\Shared;

use App\Models\AcademicYear;
use App\Models\StudentPeriodicRecord;
use App\Models\User;

trait HandlesPeriodicRecord
{
    public bool $periodicModal = false;

    public float $weight = 0;

    public float $height = 0;

    public float $head_circumference = 0;

    public int $semester = 1;

    public ?int $current_academic_year_id = null;

    public bool $hasExistingPeriodicData = false;

    public ?string $periodicDataLastUpdated = null;

    public ?User $editingUserForPeriodic = null;

    public function mountHandlesPeriodicRecord(): void
    {
        $this->current_academic_year_id = AcademicYear::where('is_active', true)->first()?->id;
    }

    public function openPeriodic(User $user): void
    {
        $this->editingUserForPeriodic = $user;
        $this->loadPeriodicData();
        $this->periodicModal = true;
    }

    public function updatedSemester(): void
    {
        $this->loadPeriodicData();
    }

    protected function loadPeriodicData(): void
    {
        if ($this->editingUserForPeriodic) {
            $profile = $this->editingUserForPeriodic->latestProfile?->profileable;

            if ($profile) {
                $existingRecord = StudentPeriodicRecord::where('student_profile_id', $profile->id)
                    ->where('academic_year_id', $this->current_academic_year_id)
                    ->where('semester', $this->semester)
                    ->first();

                if ($existingRecord) {
                    $this->weight = $existingRecord->weight;
                    $this->height = $existingRecord->height;
                    $this->head_circumference = $existingRecord->head_circumference;
                    $this->hasExistingPeriodicData = true;
                    $this->periodicDataLastUpdated = $existingRecord->updated_at->diffForHumans();
                } else {
                    $this->resetPeriodicFields();
                }
            }
        }
    }

    protected function resetPeriodicFields(): void
    {
        $this->weight = 0;
        $this->height = 0;
        $this->head_circumference = 0;
        $this->hasExistingPeriodicData = false;
        $this->periodicDataLastUpdated = null;
    }

    public function savePeriodic(): void
    {
        $profile = $this->editingUserForPeriodic?->latestProfile?->profileable;

        if (! $profile) {
            session()->flash('error', 'Data profil siswa tidak ditemukan.');

            return;
        }

        $this->validate([
            'weight' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'head_circumference' => 'required|numeric|min:0',
            'semester' => 'required|integer|in:1,2',
        ]);

        StudentPeriodicRecord::updateOrCreate(
            [
                'student_profile_id' => $profile->id,
                'academic_year_id' => $this->current_academic_year_id,
                'semester' => $this->semester,
            ],
            [
                'weight' => $this->weight,
                'height' => $this->height,
                'head_circumference' => $this->head_circumference,
                'recorded_by' => auth()->id(),
            ],
        );

        $this->periodicModal = false;
        $this->reset(['weight', 'height', 'head_circumference', 'hasExistingPeriodicData', 'periodicDataLastUpdated']);
        session()->flash('success', __('Data periodik berhasil disimpan!'));
    }
}
