<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('nik')->nullable();
            $table->string('nisn')->nullable();
            $table->string('pob')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('province_id')->nullable();
            $table->string('province_name')->nullable();
            $table->string('regency_id')->nullable();
            $table->string('regency_name')->nullable();
            $table->string('district_id')->nullable();
            $table->string('district_name')->nullable();
            $table->string('village_id')->nullable();
            $table->string('village_name')->nullable();

            // Parent/Guardian
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('nik_ayah')->nullable();
            $table->string('nik_ibu')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('no_akta')->nullable();
            $table->integer('birth_order')->nullable();
            $table->integer('total_siblings')->nullable();

            // Academic
            $table->string('previous_school')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('preferred_level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();

            // Status & Enrollment
            $table->string('status')->default('pending'); // pending, accepted, rejected, enrolled
            $table->text('notes')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
