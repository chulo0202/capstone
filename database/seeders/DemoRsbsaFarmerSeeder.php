<?php

namespace Database\Seeders;

use App\Models\RsbsaFarmer;
use Illuminate\Database\Seeder;

class DemoRsbsaFarmerSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Juan Demo Dela Cruz', 'Maria Demo Santos', 'Jose Demo Reyes',
            'Ana Demo Garcia', 'Pedro Demo Ramos', 'Rosa Demo Mendoza',
            'Luis Demo Flores', 'Elena Demo Cruz', 'Marco Demo Torres',
            'Lina Demo Navarro', 'Ramon Demo Bautista', 'Celia Demo Rivera',
            'Paolo Demo Aquino', 'Luz Demo Castillo', 'Diego Demo Villanueva',
            'Mila Demo Gonzales', 'Mario Demo Lopez', 'Teresa Demo Mercado',
            'Carlos Demo Perez', 'Nina Demo Valdez',
        ];

        $barangays = ['Gate', 'Danao', 'Inararan', 'Butag', 'San Vicente'];
        $crops = ['Rice', 'Corn', 'Vegetables', 'Fruits', 'Coconut'];

        foreach ($names as $index => $name) {
            $number = sprintf('RSBSA-DEMO-%04d', $index + 1);

            RsbsaFarmer::firstOrCreate(['rsbsa_number' => $number], [
                'full_name' => $name,
                'address' => 'Purok '.(5 + $index % 4),
                'barangay' => $barangays[$index % count($barangays)],
                'birthdate' => sprintf('%04d-01-15', 1990 - $index),
                'crop_type' => $crops[$index % count($crops)],
                'land_size' => 1.25 + $index * 0.25,
            ]);
        }
    }
}
