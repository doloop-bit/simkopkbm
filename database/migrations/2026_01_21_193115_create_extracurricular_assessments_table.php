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
        Schema::create('extracurricular_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('extracurricular_activity_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->enum('semester', ['1', '2']);
            $table->enum('achievement_level', ['BB', 'MB', 'BSH', 'SB'])->comment('Level pencapaian ekstrakurikuler');
            $table->text('description')->nullable()->comment('Deskripsi pencapaian siswa');
            $table->timestamps();

            $table->unique(['student_id', 'extracurricular_activity_id', 'academic_year_id', 'semester'], 'unique_extracurricular_assessment');
            $table->index(['academic_year_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracurricular_assessments');
    }
};
