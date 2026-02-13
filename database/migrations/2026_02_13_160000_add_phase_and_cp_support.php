<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add class_level (tingkat kelas) to classrooms
        //    Distinct from "grade" (nilai) - this represents the class number within a paket
        //    e.g. Paket A kelas 1-6, Paket B kelas 1-3, Paket C kelas 1-3
        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('class_level')->nullable()->after('name');
        });

        // 2. Add phase_map JSON to levels
        //    Maps class_level to Kurikulum Merdeka phase (A-F)
        //    e.g. {"1": "A", "2": "A", "3": "B", "4": "B", "5": "C", "6": "C"}
        Schema::table('levels', function (Blueprint $table) {
            $table->json('phase_map')->nullable()->after('education_level');
        });

        // 3. Clear any existing TP references in subject_grades (they reference old subject_tps)
        if (Schema::hasTable('subject_grades')) {
            DB::table('subject_grades')->update([
                'best_tp_id' => null,
                'improvement_tp_id' => null,
            ]);
        }

        // 4. Rebuild subject_tps to link to learning_achievements (CP) instead of subjects
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subject_tps');
        Schema::create('subject_tps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_achievement_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->nullable();
            $table->text('description');
            $table->timestamps();

            $table->index('learning_achievement_id', 'subject_tps_la_id_index');
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('class_level');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn('phase_map');
        });

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subject_tps');
        Schema::create('subject_tps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->nullable();
            $table->text('description');
            $table->timestamps();
        });
        Schema::enableForeignKeyConstraints();
    }
};
