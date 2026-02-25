<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('level_id')
                ->nullable()
                ->after('id')
                ->constrained('levels')
                ->cascadeOnDelete();
        });

        // Migrate existing data: map level enum to level_id
        $levelMapping = [
            'paud' => DB::table('levels')->where('education_level', 'paud')->value('id'),
            'paket_a' => DB::table('levels')->where('education_level', 'sd')->value('id'),
            'paket_b' => DB::table('levels')->where('education_level', 'smp')->value('id'),
            'paket_c' => DB::table('levels')->where('education_level', 'sma')->value('id'),
        ];

        foreach ($levelMapping as $enum => $levelId) {
            if ($levelId) {
                DB::table('programs')
                    ->where('level', $enum)
                    ->update(['level_id' => $levelId]);
            }
        }

        // Drop the old level enum column
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('level')->nullable()->after('slug');
        });

        // Restore data from level_id back to level enum
        $levels = DB::table('levels')->get();
        foreach ($levels as $level) {
            $enumValue = match ($level->education_level) {
                'paud' => 'paud',
                'sd' => 'paket_a',
                'smp' => 'paket_b',
                'sma' => 'paket_c',
                default => null,
            };

            if ($enumValue) {
                DB::table('programs')
                    ->where('level_id', $level->id)
                    ->update(['level' => $enumValue]);
            }
        }

        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }
};
