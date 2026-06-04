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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                'rapat_jenjang',
                'rapat_gabungan',
                'rapat_yayasan',
                'ujian_dinas',
                'ujian_sekolah',
                'kegiatan',
                'lainnya',
            ]);
            $table->enum('scope', ['level', 'pkbm', 'yayasan'])->default('level');
            $table->foreignId('level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly'])->default('none');
            $table->json('recurrence_config')->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->unsignedBigInteger('parent_event_id')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('parent_event_id')->references('id')->on('calendar_events')->nullOnDelete();
            $table->index(['start_date', 'end_date']);
            $table->index(['academic_year_id', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
