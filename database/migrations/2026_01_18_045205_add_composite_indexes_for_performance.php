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
        Schema::table('news_articles', function (Blueprint $table) {
            // Composite index for published articles ordered by date
            $table->index(['status', 'published_at', 'created_at'], 'news_published_date_idx');
        });

        Schema::table('gallery_photos', function (Blueprint $table) {
            // Composite index for published photos by category and order
            $table->index(['is_published', 'category', 'order'], 'gallery_published_category_order_idx');
            // Composite index for published photos ordered
            $table->index(['is_published', 'order', 'created_at'], 'gallery_published_order_idx');
        });

        Schema::table('programs', function (Blueprint $table) {
            // Composite index for active programs ordered
            $table->index(['is_active', 'order'], 'programs_active_order_idx');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            // Composite index for staff by school profile and order
            $table->index(['school_profile_id', 'order'], 'staff_profile_order_idx');
        });

        Schema::table('facilities', function (Blueprint $table) {
            // Composite index for facilities by school profile and order
            $table->index(['school_profile_id', 'order'], 'facilities_profile_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropIndex('news_published_date_idx');
        });

        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropIndex('gallery_published_category_order_idx');
            $table->dropIndex('gallery_published_order_idx');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex('programs_active_order_idx');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropIndex('staff_profile_order_idx');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex('facilities_profile_order_idx');
        });
    }
};
