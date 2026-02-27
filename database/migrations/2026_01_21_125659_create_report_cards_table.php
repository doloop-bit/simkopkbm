<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->json('scores')->nullable(); // Store aggregated scores by subject
            $table->decimal('gpa', 5, 2)->nullable(); // Grade Point Average
            $table->string('semester')->default('1'); // Semester 1 or 2
            $table->text('teacher_notes')->nullable();
            $table->text('principal_notes')->nullable();
            $table->string('status')->default('draft'); // draft, finalized, printed
            $table->timestamps();

            $table->unique(['student_id', 'classroom_id', 'academic_year_id', 'semester'], 'rc_student_class_year_sem_unique');
            $table->index(['academic_year_id', 'classroom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
