<?php

namespace Database\Seeders;

use App\Models\RsbsaFarmer;
use Illuminate\Database\Seeder;

class DemoRsbsaFarmerSeeder extends Seeder
{
    public function run(): void
    {
        RsbsaFarmer::firstOrCreate(['rsbsa_number' => 'RSBSA-DEMO-0001'], [
            'full_name' => 'Juan Demo Dela Cruz',
            'address' => 'Purok 5',
            'barangay' => 'Gate',
            'birthdate' => '1990-01-15',
            'crop_type' => 'Rice',
            'land_size' => 1.25,
        ]);
    }
}
