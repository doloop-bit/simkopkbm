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
        Schema::create('developmental_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('developmental_aspect_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('classroom_id')->constrained()->onDelete('cascade');
            $table->enum('semester', ['1', '2']);
            $table->text('description')->comment('Deskripsi narasi perkembangan anak');
            $table->timestamps();

            $table->unique(['student_id', 'developmental_aspect_id', 'academic_year_id', 'semester'], 'unique_dev_assessment');
            $table->index(['classroom_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developmental_assessments');
    }
};
