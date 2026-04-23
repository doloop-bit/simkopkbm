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
        Schema::create('diniyah_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('diniyah_subject_id')->constrained('diniyah_subjects')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('semester', 1)->default('1');

            // For 'numeric' type
            $table->decimal('knowledge_grade', 5, 2)->nullable();
            $table->decimal('practice_grade', 5, 2)->nullable();
            $table->enum('attitude_grade', ['A', 'B', 'C', 'D'])->nullable();

            // For 'target_achievement' type
            $table->string('achievement')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diniyah_grades');
    }
};
