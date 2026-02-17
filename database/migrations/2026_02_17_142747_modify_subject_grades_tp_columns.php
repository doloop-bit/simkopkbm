<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subject_grades', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_grades', 'best_tp_ids')) {
                $table->json('best_tp_ids')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('subject_grades', 'improvement_tp_ids')) {
                $table->json('improvement_tp_ids')->nullable()->after('best_tp_ids');
            }
        });

        // Migrate data
        DB::statement('UPDATE subject_grades SET best_tp_ids = JSON_ARRAY(CAST(best_tp_id AS CHAR)) WHERE best_tp_id IS NOT NULL');
        DB::statement('UPDATE subject_grades SET improvement_tp_ids = JSON_ARRAY(CAST(improvement_tp_id AS CHAR)) WHERE improvement_tp_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_grades', function (Blueprint $table) {
            $table->dropColumn(['best_tp_ids', 'improvement_tp_ids']);
        });
    }
};
