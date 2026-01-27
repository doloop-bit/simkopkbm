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
        Schema::table('report_cards', function (Blueprint $table) {
            // Add curriculum type field
            $table->enum('curriculum_type', ['conventional', 'merdeka'])
                ->default('conventional')
                ->after('semester')
                ->comment('Tipe kurikulum: conventional (lama) atau merdeka');
            
            // Add character notes for Kurikulum Merdeka
            $table->text('character_notes')
                ->nullable()
                ->after('principal_notes')
                ->comment('Catatan perkembangan karakter siswa');
            
            // Keep existing fields for backward compatibility
            // scores (JSON) - untuk conventional
            // gpa (decimal) - untuk conventional
            // teacher_notes, principal_notes - digunakan untuk kedua tipe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropColumn(['curriculum_type', 'character_notes']);
        });
    }
};
