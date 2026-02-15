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
        Schema::create('budget_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // e.g., "RAB Januari 2026"
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'transferred'])->default('draft');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('notes')->nullable(); // For rejection reasons or other notes
            $table->timestamps();
        });

        Schema::create('budget_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standard_budget_item_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Snapshot Name, just in case master data changes
            $table->integer('quantity');
            $table->string('unit'); // Snapshot Unit
            $table->decimal('amount', 12, 2); // Unit Price
            $table->decimal('total', 15, 2); // Calculated Total
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_plan_items');
        Schema::dropIfExists('budget_plans');
    }
};
