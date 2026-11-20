<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_notification_reads', function (Blueprint $table) {
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->unsignedBigInteger('notification_id');
            $table->primary(['farmer_id', 'source', 'notification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_notification_reads');
    }
};
