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
        Schema::table('levels', function (Blueprint $table) {
            $table->foreignId('financial_unit_id')->nullable()->after('education_level')->constrained('financial_units')->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('financial_unit_id')->nullable()->after('id')->constrained('financial_units')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('managed_financial_unit_id')->nullable()->after('managed_level_id')->constrained('financial_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('managed_financial_unit_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_unit_id');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('financial_unit_id');
        });
    }
};
