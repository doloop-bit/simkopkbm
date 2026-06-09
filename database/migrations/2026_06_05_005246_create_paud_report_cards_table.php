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
        Schema::create('paud_report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('semester');
            $table->json('cp_summaries')->nullable();
            $table->string('display_mode')->default('cp');
            $table->text('teacher_notes')->nullable();
            $table->text('parent_reflection')->nullable();
            $table->json('attendance')->nullable();
            $table->json('physical_data')->nullable();
            $table->string('access_token')->nullable()->unique();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();

            $table->unique(['student_id', 'classroom_id', 'academic_year_id', 'semester'], 'paud_rc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paud_report_cards');
    }
};
