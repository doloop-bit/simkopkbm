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
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('attachment')->nullable()->change();
        });

        // Migrate existing string-based attachments to JSON arrays
        DB::table('transactions')
            ->whereNotNull('attachment')
            ->get()
            ->each(function ($transaction) {
                $current = $transaction->attachment;

                // If it doesn't look like a JSON array, convert it to one
                if (! str_starts_with($current, '[') && ! str_starts_with($current, '{')) {
                    DB::table('transactions')
                        ->where('id', $transaction->id)
                        ->update(['attachment' => json_encode([$current])]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('attachment')->nullable()->change();
        });
    }
};
