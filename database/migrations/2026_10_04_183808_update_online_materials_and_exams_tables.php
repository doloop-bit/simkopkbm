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
        Schema::table('online_materials', function (Blueprint $table) {
            $table->foreignId('chapter_id')->nullable()->after('subject_id')->constrained('material_chapters')->onDelete('cascade');
            $table->string('type')->default('text')->after('title'); // handbook, text, slides, video
            $table->string('video_url')->nullable()->after('type');
            $table->longText('transcript')->nullable()->after('video_url');
        });

        Schema::table('online_exams', function (Blueprint $table) {
            $table->foreignId('chapter_id')->nullable()->after('subject_id')->constrained('material_chapters')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('online_exams', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn('chapter_id');
        });

        Schema::table('online_materials', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn(['chapter_id', 'type', 'video_url', 'transcript']);
        });
    }
};
