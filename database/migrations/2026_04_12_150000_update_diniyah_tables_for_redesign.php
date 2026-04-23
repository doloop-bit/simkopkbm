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
        Schema::table('diniyah_subjects', function (Blueprint $table) {
            $table->integer('kkm')->default(70)->after('assessment_type');
        });

        Schema::table('diniyah_grades', function (Blueprint $table) {
            $table->string('target_status')->nullable()->after('achievement');
            // Check if column exists before dropping to be safe
            if (Schema::hasColumn('diniyah_grades', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diniyah_subjects', function (Blueprint $table) {
            $table->dropColumn('kkm');
        });

        Schema::table('diniyah_grades', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('grade');
            $table->dropColumn('target_status');
        });
    }
};
