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
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('phase')->nullable()->after('level_id');
            // Make level_id nullable as it is being replaced
            $table->unsignedBigInteger('level_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('phase');
            // Reverting nullable might be risky if we have nulls, but for strict reverse:
            $table->unsignedBigInteger('level_id')->nullable(false)->change();
        });
    }
};
