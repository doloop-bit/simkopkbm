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
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->string('medium_path')->nullable()->after('web_path');
            $table->string('small_path')->nullable()->after('medium_path');
            $table->string('original_webp_path')->nullable()->after('small_path');
            $table->string('thumbnail_webp_path')->nullable()->after('original_webp_path');
            $table->string('web_webp_path')->nullable()->after('thumbnail_webp_path');
            $table->string('medium_webp_path')->nullable()->after('web_webp_path');
            $table->string('small_webp_path')->nullable()->after('medium_webp_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropColumn([
                'medium_path',
                'small_path',
                'original_webp_path',
                'thumbnail_webp_path',
                'web_webp_path',
                'medium_webp_path',
                'small_webp_path',
            ]);
        });
    }
};
