<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $programIds = DB::table('programs')->whereIn('name', [
                'Infrastructure Support',
                'Equipment and Machinery Support',
                'Post-Harvest Support',
                'Marketing Support',
            ])->pluck('id');

            DB::table('programs')->whereIn('id', $programIds)->whereNull('archived_at')
                ->update(['archived_at' => $now, 'is_active' => false, 'updated_at' => $now]);

            DB::table('program_benefits')->whereIn('program_id', $programIds)->whereNull('archived_at')
                ->update(['archived_at' => $now, 'always_active' => false, 'updated_at' => $now]);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Automatic rollback disabled to preserve existing archive states.');
    }
};
