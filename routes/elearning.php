<?php

use Illuminate\Support\Facades\Route;

// Admin E-Learning Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/elearning/materials', 'admin.elearning.materials')
        ->name('elearning.materials');

    Route::livewire('/elearning/exams', 'admin.elearning.exams')
        ->name('elearning.exams');

    Route::livewire('/elearning/exams/{examId}/questions', 'admin.elearning.exam-questions')
        ->name('elearning.exam-questions');

    Route::livewire('/elearning/grade-recap', 'admin.elearning.grade-recap')
        ->name('elearning.grade-recap');

    Route::livewire('/elearning/submissions/{submissionId}', 'admin.elearning.submission-detail')
        ->name('elearning.submission-detail');
});

// Student E-Learning Routes
Route::middleware(['auth'])->prefix('student')->name('student.')->group(function () {
    Route::livewire('/dashboard', 'student.dashboard')
        ->name('dashboard');

    Route::livewire('/materials', 'student.materials')
        ->name('materials');

    Route::livewire('/materials/{materialId}', 'student.material-detail')
        ->name('material-detail');

    Route::livewire('/exams', 'student.exams')
        ->name('exams');

    Route::livewire('/exams/{examId}/take', 'student.take-exam')
        ->name('take-exam');

    Route::livewire('/exams/{submissionId}/result', 'student.exam-result')
        ->name('exam-result');
});

// Shared / Authenticated Download Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/elearning/exams/{exam}/attachment', [\App\Http\Controllers\ElearningDownloadController::class, 'downloadExamAttachment'])
        ->name('elearning.exams.download-attachment');

    Route::get('/elearning/questions/{question}/attachment', [\App\Http\Controllers\ElearningDownloadController::class, 'downloadQuestionAttachment'])
        ->name('elearning.questions.download-attachment');
});
