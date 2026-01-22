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
        Schema::create('competency_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->enum('semester', ['1', '2']);
            $table->enum('competency_level', ['BB', 'MB', 'BSH', 'SB'])->comment('BB=Belum Berkembang, MB=Mulai Berkembang, BSH=Berkembang Sesuai Harapan, SB=Sangat Berkembang');
            $table->text('achievement_description')->comment('Deskripsi capaian siswa untuk mata pelajaran ini');
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id', 'semester'], 'unique_competency_assessment');
            $table->index(['classroom_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency_assessments');
    }
};
