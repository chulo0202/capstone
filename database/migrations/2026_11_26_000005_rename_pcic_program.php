<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_NAME = 'PCIC Crop Insurance';
    private const NEW_NAME = 'Philippine Crop Insurance Corporation (PCIC)';

    public function up(): void
    {
        DB::table('programs')->where('name', self::OLD_NAME)->update(['name' => self::NEW_NAME]);
    }

    public function down(): void
    {
        DB::table('programs')->where('name', self::NEW_NAME)->update(['name' => self::OLD_NAME]);
    }
};
