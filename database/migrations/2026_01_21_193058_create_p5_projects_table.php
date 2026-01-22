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
        Schema::create('p5_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->enum('semester', ['1', '2']);
            $table->enum('dimension', [
                'beriman',
                'berkebinekaan',
                'gotong_royong',
                'mandiri',
                'bernalar_kritis',
                'kreatif'
            ])->comment('Dimensi Profil Pelajar Pancasila');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p5_projects');
    }
};
