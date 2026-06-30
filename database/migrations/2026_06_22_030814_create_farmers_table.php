<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->text('address');
            $table->string('barangay');
            $table->string('contact_number', 20);
            $table->date('birthdate');
            $table->string('crop_type');
            $table->decimal('land_size', 10, 2);
            $table->boolean('rsbsa_status')->default(false);
            $table->string('rsbsa_number')->nullable();
            $table->boolean('association_membership')->default(false);
            $table->string('valid_id_path')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('qr_code_token')->unique()->nullable();
            $table->boolean('profile_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
