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
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->enum('billing_type', ['monthly', 'one_time'])->default('monthly')->after('default_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
