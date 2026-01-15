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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('nik')->nullable()->after('nisn');
            $table->string('nik_ayah')->nullable()->after('father_name');
            $table->string('nik_ibu')->nullable()->after('mother_name');
            $table->string('no_kk')->nullable()->after('nik_ibu');
            $table->string('no_akta')->nullable()->after('no_kk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['nik', 'nik_ayah', 'nik_ibu', 'no_kk', 'no_akta']);
        });
    }
};
