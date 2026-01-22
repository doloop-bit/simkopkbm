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
        Schema::create('learning_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->enum('phase', ['A', 'B', 'C', 'D', 'E', 'F'])->comment('Fase pembelajaran sesuai Kurikulum Merdeka');
            $table->text('description')->comment('Deskripsi capaian pembelajaran');
            $table->timestamps();

            $table->index(['subject_id', 'phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_achievements');
    }
};
