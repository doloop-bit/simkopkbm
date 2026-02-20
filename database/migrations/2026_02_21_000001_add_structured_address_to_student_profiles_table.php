<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('province_name')->nullable()->after('address');
            $table->string('regency_name')->nullable()->after('province_name');
            $table->string('district_name')->nullable()->after('regency_name');
            $table->string('village_name')->nullable()->after('district_name');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['province_name', 'regency_name', 'district_name', 'village_name']);
        });
    }
};
