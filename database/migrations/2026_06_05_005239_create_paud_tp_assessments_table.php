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
        Schema::create('paud_tp_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paud_tp_id')->constrained('paud_tps')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('level', ['BB', 'MB', 'BSH', 'BSB']);
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->foreignId('assessed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['paud_tp_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paud_tp_assessments');
    }
};
