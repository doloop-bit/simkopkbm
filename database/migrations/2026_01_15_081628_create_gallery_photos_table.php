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
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('caption')->nullable();
            $table->string('category', 100);
            $table->string('original_path');
            $table->string('thumbnail_path');
            $table->string('web_path');
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_published');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
