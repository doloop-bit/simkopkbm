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
        Schema::create('diniyah_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('assessment_type', ['numeric', 'target_achievement']);
            $table->string('target')->nullable(); // Target for all students (target_achievement type)
            $table->boolean('has_practice')->default(false); // Only for numeric type
            $table->foreignId('level_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diniyah_subjects');
    }
};
