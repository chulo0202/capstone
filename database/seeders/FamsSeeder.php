<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\EligibilityRule;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\ProgramBenefit;
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
                'name' => 'Seed Subsidies',
                'description' => 'Seed assistance and planting materials for eligible farmers.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, land size, RSBSA status, and other applicable requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.1,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'Fertilizer Subsidies',
                'description' => 'Fertilizer assistance for eligible farmers.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, land size, RSBSA status, and other applicable requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables'],
                    'min_land_size' => 0.1,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'Equipment and Machinery Support',
                'description' => 'Agricultural equipment and machinery assistance for eligible farmers.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, land size, RSBSA status, and other applicable requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.5,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => true,
                ],
            ],

            [
                'name' => 'Agricultural Loans',
                'description' => 'Agricultural financing assistance for eligible farmers.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, land size, RSBSA status, and other applicable requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.1,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'Training and Technology Demonstrations',
                'description' => 'Training, seminars, workshops, and agricultural technology demonstrations for farmers.',
                'eligibility_criteria' => 'Eligibility is determined based on farmer registration and applicable training requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => null,
                    'max_land_size' => null,
                    'requires_rsbsa' => false,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'Marketing Support',
                'description' => 'Market linkage and marketing assistance for agricultural products.',
                'eligibility_criteria' => 'Eligibility is determined based on farmer registration, agricultural products, and applicable market requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.1,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'PCIC Crop Insurance',
                'description' => 'Crop insurance assistance and financial protection for eligible agricultural producers.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, farmer registration, and applicable insurance requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables'],
                    'min_land_size' => 0.1,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => false,
                ],
            ],

            [
                'name' => 'Infrastructure Support',
                'description' => 'Support for agricultural infrastructure and farm-related facilities.',
                'eligibility_criteria' => 'Eligibility is determined based on agricultural activity, location, farmer registration, and applicable infrastructure requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.5,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
                    'requires_association' => true,
                ],
            ],

            [
                'name' => 'Post-Harvest Support',
                'description' => 'Post-harvest equipment, facilities, and assistance for agricultural products.',
                'eligibility_criteria' => 'Eligibility is determined based on crop type, agricultural activity, farmer registration, and applicable post-harvest requirements.',
                'rules' => [
                    'crop_types' => ['Rice', 'Corn', 'Vegetables', 'Root crops'],
                    'min_land_size' => 0.5,
                    'max_land_size' => null,
                    'requires_rsbsa' => true,
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

            /*
            |--------------------------------------------------------------------------
            | Program Benefits
            |--------------------------------------------------------------------------
            */
            $benefits = match ($program->name) {
                'Seed Subsidies' => [
                    'Certified seeds (palay, mais)',
                    'Hybrid seeds',
                    'Quality planting materials',
                    'Libre o subsidized na binhi',
                    'Seed treatment chemicals',
                ],

                'Fertilizer Subsidies' => [
                    'Complete fertilizer (NPK)',
                    'Urea',
                    'Ammonium Sulfate',
                    'Organic fertilizer',
                    'Subsidy voucher para sa pagbili ng pataba',
                ],

                'Equipment and Machinery Support' => [
                    'Power tiller',
                    'Hand tractor',
                    'Forage chopper',
                    'Mobile disinfection truck',
                    'Rice thresher',
                    'Water pump',
                    'Equipment grant o subsidized na pagbili',
                ],

                'Agricultural Loans' => [
                    'Pautang na may mababang interes',
                    'Maximum na loan assistance',
                    'Flexible payment terms',
                    'Production loan para sa farm inputs',
                ],

                'Training and Technology Demonstrations' => [
                    'Libreng seminar at workshop',
                    'Farmer Field School (FFS)',
                    'Climate Resiliency Field School',
                    'Good Agricultural Practices (GAP) training',
                    'Teknikal na kaalaman sa modernong pagsasaka',
                    'Demonstration ng bagong teknolohiya',
                ],

                'Marketing Support' => [
                    'Tulong sa pag-access sa merkado',
                    'Kadiwa participation',
                    'Direktang market linkage',
                    'Trade fair participation',
                    'Pag-uugnay sa mga mamimili at negosyante',
                    'Market linkage assistance',
                ],

                'PCIC Crop Insurance' => [
                    'Insurance coverage para sa mga pananim',
                    'Proteksyon laban sa mga sakuna',
                    'Bayad-pinsala kapag may crop loss',
                    'Coverage para sa palay, mais, at iba pang pananim',
                    'Financial protection para sa mga magsasaka',
                ],

                'Infrastructure Support' => [
                    'Post-harvest facilities',
                    'Irrigation system',
                    'Farm-to-market roads',
                    'Storage facilities',
                    'Solar dryer',
                    'Multi-purpose drying pavement',
                    'Small water impounding system',
                ],

                'Post-Harvest Support' => [
                    'Rice mill o thresher',
                    'Mechanical dryer',
                    'Storage facilities',
                    'Packaging materials',
                    'Transport assistance',
                    'Kagamitan para sa pag-process ng ani',
                    'Tulong sa pagbebenta ng ani',
                ],

                default => [],
            };

            /*
            |--------------------------------------------------------------------------
            | Save Benefits
            |--------------------------------------------------------------------------
            */
            foreach ($benefits as $benefitName) {
                ProgramBenefit::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'name' => $benefitName,
                    ],
                    [
                        'description' => null,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Default Eligibility Rule
            |--------------------------------------------------------------------------
            */
            EligibilityRule::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'program_benefit_id' => null,
                ],
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
                    'rsbsa_status' => true,
                    'rsbsa_number' => 'FAMS-SEED-'.str_pad($i, 3, '0', STR_PAD_LEFT),
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
