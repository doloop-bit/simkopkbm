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
        Schema::create('online_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('online_exam_submissions')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('online_exam_questions')->onDelete('cascade');
            $table->text('answer_text')->nullable();
            $table->string('answer_file')->nullable();
            $table->string('selected_option')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_exam_answers');
    }
};
