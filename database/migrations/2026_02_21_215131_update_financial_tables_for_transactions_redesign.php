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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type')->default('income')->after('id');
            $table->foreignId('student_billing_id')->nullable()->change();

            // New fields for expense
            $table->foreignId('budget_plan_id')->nullable()->after('student_billing_id')->constrained()->nullOnDelete();
            $table->foreignId('budget_plan_item_id')->nullable()->after('budget_plan_id')->constrained()->nullOnDelete();
        });

        Schema::table('budget_plans', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_plans', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['budget_plan_item_id']);
            $table->dropForeign(['budget_plan_id']);
            $table->dropColumn(['type', 'budget_plan_id', 'budget_plan_item_id']);
        });
    }
};
