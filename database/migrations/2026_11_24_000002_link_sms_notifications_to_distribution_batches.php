<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_notifications', function (Blueprint $table) {
            $table->foreignId('distribution_batch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['distribution_batch_id', 'farmer_id'], 'sms_batch_farmer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sms_notifications', function (Blueprint $table) {
            $table->dropUnique('sms_batch_farmer_unique');
            $table->dropConstrainedForeignId('distribution_batch_id');
        });
    }
};
