<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\EligibilityRule;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FamsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@fams.local'],
            [
                'name' => 'MAO Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $programs = [
            [
                'name' => 'Rice Subsidy Program',
                'description' => 'Financial and seed assistance for registered rice farmers.',
                'eligibility_criteria' => 'Rice farmers with RSBSA registration and minimum 0.5 hectares.',
                'rules' => [
                    'crop_types' => ['Rice'],
                    'min_land_size' => 0.5,
                    'max_land_size' => 5,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],
            [
                'name' => 'Corn Production Support',
                'description' => 'Fertilizer and equipment support for corn farmers.',
                'eligibility_criteria' => 'Corn farmers with at least 1 hectare of farmland.',
                'rules' => [
                    'crop_types' => ['Corn'],
                    'min_land_size' => 1,
                    'max_land_size' => null,
                    'requires_rsbsa' => false,
                    'requires_association' => true,
                ],
            ],
            [
                'name' => 'Vegetable Seeds Distribution',
                'description' => 'Free vegetable seeds for small-scale farmers.',
                'eligibility_criteria' => 'Vegetable farmers with land up to 2 hectares.',
                'rules' => [
                    'crop_types' => ['Vegetables'],
                    'min_land_size' => 0.1,
                    'max_land_size' => 2,
                    'requires_rsbsa' => false,
                    'requires_association' => false,
                ],
            ],
        ];

        foreach ($programs as $data) {
            $rules = $data['rules'];
            unset($data['rules']);

            $program = Program::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            EligibilityRule::updateOrCreate(
                ['program_id' => $program->id],
                $rules
            );
        }

        $barangays = ['Poblacion', 'San Jose', 'Santa Maria', 'San Isidro', 'San Antonio'];
        $crops = ['Rice', 'Corn', 'Vegetables', 'Coconut', 'Fruits'];

        for ($i = 1; $i <= 10; $i++) {
            $user = User::updateOrCreate(
                ['email' => "farmer{$i}@fams.local"],
                [
                    'name' => "Farmer {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'farmer',
                    'email_verified_at' => now(),
                ]
            );

            Farmer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => "Farmer {$i} Sample",
                    'address' => "{$i} Farm Road, Municipality",
                    'barangay' => $barangays[array_rand($barangays)],
                    'contact_number' => '09'.rand(100000000, 999999999),
                    'birthdate' => now()->subYears(rand(25, 60))->format('Y-m-d'),
                    'crop_type' => $crops[array_rand($crops)],
                    'land_size' => rand(50, 500) / 100,
                    'rsbsa_status' => (bool) rand(0, 1),
                    'rsbsa_number' => rand(0, 1) ? 'RSBSA-'.rand(10000, 99999) : null,
                    'association_membership' => (bool) rand(0, 1),
                    'profile_completed' => true,
                    'qr_code_token' => Str::uuid()->toString(),
                ]
            );
        }

        Announcement::updateOrCreate(
            ['title' => 'Welcome to FAMS'],
            [
                'content' => 'The Farmer Assistance Management System is now live. Please complete your profile and check recommended programs.',
                'publish_date' => now(),
                'sms_enabled' => false,
                'is_published' => true,
                'created_by' => $admin->id,
            ]
        );

        $farmer = Farmer::first();
        $program = Program::first();

        if ($farmer && $program) {
            Distribution::firstOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'program_id' => $program->id,
                ],
                [
                    'released_by' => $admin->id,
                    'distributed_at' => now()->subDays(3),
                    'notes' => 'Initial seed distribution',
                ]
            );
        }

        app(RecommendationService::class)->refreshAll();
    }
}
