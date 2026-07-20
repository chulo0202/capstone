<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->boolean('four_ps_membership')->default(false)->after('association_membership');
            $table->string('farmer_association')->nullable()->after('four_ps_membership');
            $table->string('farmer_photo_path')->nullable()->after('valid_id_path');
        });
    }

    public function down(): void
    {
        Schema::table('farmers', function (Blueprint $table) {
            $table->dropColumn(['four_ps_membership', 'farmer_association', 'farmer_photo_path']);
        });
    }
};
