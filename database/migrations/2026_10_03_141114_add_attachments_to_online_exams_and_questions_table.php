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
        Schema::table('online_exams', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('description');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });

        Schema::table('online_exam_questions', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('question_text');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_exams', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });

        Schema::table('online_exam_questions', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
