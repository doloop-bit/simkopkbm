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
        Schema::create('developmental_aspects', function (Blueprint $table) {
            $table->id();
            $table->enum('aspect_type', [
                'nilai_agama',
                'fisik_motorik',
                'kognitif',
                'bahasa',
                'sosial_emosional',
                'seni'
            ])->comment('Jenis aspek perkembangan PAUD');
            $table->string('name')->comment('Nama aspek perkembangan');
            $table->text('description')->nullable()->comment('Deskripsi aspek');
            $table->timestamps();

            $table->index('aspect_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developmental_aspects');
    }
};
