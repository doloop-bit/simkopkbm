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
        Schema::table('student_fee_discounts', function (Blueprint $table) {
            $table->enum('frequency', ['recurring', 'once'])->default('recurring')->after('amount');
            $table->boolean('is_applied')->default(false)->after('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fee_discounts', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'is_applied']);
        });
    }
};
