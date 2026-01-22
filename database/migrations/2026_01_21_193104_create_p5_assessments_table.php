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
        Schema::create('p5_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('p5_project_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->enum('semester', ['1', '2']);
            $table->enum('achievement_level', ['BB', 'MB', 'BSH', 'SB'])->comment('Level pencapaian P5');
            $table->text('description')->comment('Deskripsi pencapaian siswa dalam projek P5');
            $table->timestamps();

            $table->unique(['student_id', 'p5_project_id'], 'unique_p5_assessment');
            $table->index(['classroom_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p5_assessments');
    }
};
