<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProgramBenefitReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_restores_the_approved_inventory_and_preserves_history(): void
    {
        $approved = [
            'Agricultural Loans' => ['Agri-Negosyo (ANYO) Loan', 'Survival and Recovery (SURE) Loan', 'Agri-Puhunan at Pantawid', 'Young Agripreneurs Program (YAP)'],
            'Equipment and Machinery Support' => ['Combine Harvester', '4-Wheel Tractor'],
            'Fertilizer Subsidies' => ['Urea', 'Complete Fertilizer (NPK/Triple 14)', 'Foliar Fertilizer'],
            'Infrastructure Support' => ['Farm-to-Market Roads (FMR)', 'Communal Irrigation Canal'],
            'Marketing Support' => ['Kadiwa ng Pangulo Market Linkage', 'Agri-Trade Fair Participation', 'Bagsakan Center/Trading Post Access'],
            'PCIC Crop Insurance' => ['New Application', 'Claim for Indemnity'],
            'Post-Harvest Support' => ['Mechanical/Solar Grain Dryer', 'Warehouse & Crop Storage', 'Rice Processing Center (RPC)'],
            'Seed Subsidies' => ['Certified Seeds (Palay/Mais)', 'Hybrid Seeds', 'Quality Planting Materials'],
            'Training and Technology Demonstrations' => ['Techno-Demo Farm Visit (Wet and Dry Season)'],
        ];

        $programs = collect($approved)->map(fn ($benefits, $name) => Program::create([
            'name' => $name,
            'description' => 'Approved program',
            'is_active' => in_array($name, ['Infrastructure Support', 'PCIC Crop Insurance'], true),
        ]));

        $extra = Program::create(['name' => 'Additional Program', 'description' => 'New program', 'is_active' => true]);
        $extraBenefit = $extra->benefits()->create(['name' => 'Additional Benefit']);
        $infrastructure = $programs['Infrastructure Support'];
        $road = $infrastructure->benefits()->create(['name' => 'Farm-to-Market Roads (FMR)']);
        $duplicate = $infrastructure->benefits()->create(['name' => $road->name]);
        $configuration = $road->configurations()->create([
            'description' => 'Current road configuration',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
        $pcic = $programs['PCIC Crop Insurance'];
        $legacy = $pcic->benefits()->create(['name' => 'Legacy PCIC Benefit']);

        $admin = User::factory()->create(['role' => 'admin', 'admin_registration_status' => 'approved']);
        $farmer = Farmer::create([
            'user_id' => User::factory()->create(['role' => 'farmer'])->id,
            'full_name' => 'Historical Farmer',
            'address' => 'Bulan',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-HISTORY-001',
        ]);

        $records = ['farmer_id' => $farmer->id, 'program_id' => $pcic->id, 'program_benefit_id' => $legacy->id];
        $application = DB::table('applications')->insertGetId($records + ['status' => 'approved']);
        $distribution = DB::table('distributions')->insertGetId($records + [
            'application_id' => $application,
            'released_by' => $admin->id,
            'distributed_at' => now(),
        ]);
        $recommendation = DB::table('recommendations')->insertGetId($records + ['eligibility_status' => 'eligible']);

        $migration = require database_path('migrations/2026_11_19_000001_reconcile_production_program_benefits.php');
        $migration->up();
        $migration->up();

        foreach ($approved as $name => $benefits) {
            $this->assertEqualsCanonicalizing($benefits, $programs[$name]->activeBenefits()->pluck('name')->all());
        }

        $this->assertTrue($infrastructure->refresh()->is_active);
        $this->assertFalse($programs['Post-Harvest Support']->refresh()->is_active);
        $this->assertTrue($extra->refresh()->is_active);
        $this->assertNull($extraBenefit->refresh()->archived_at);
        $this->assertNotNull($duplicate->refresh()->archived_at);
        $this->assertNotNull($legacy->refresh()->archived_at);
        $this->assertTrue($pcic->activeBenefits()->where('name', 'Claim for Indemnity')->firstOrFail()->always_active);
        $this->assertFalse($pcic->activeBenefits()->where('name', 'New Application')->firstOrFail()->always_active);
        $this->assertDatabaseHas('applications', $records + ['id' => $application]);
        $this->assertFalse(Application::withCurrentBenefit()->whereKey($application)->exists());
        $currentApplication = Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $infrastructure->id,
            'program_benefit_id' => $road->id,
            'status' => 'pending',
        ]);
        $this->assertTrue(Application::withCurrentBenefit()->whereKey($currentApplication->id)->exists());
        $this->assertDatabaseHas('distributions', $records + ['id' => $distribution]);
        $this->assertDatabaseHas('recommendations', $records + ['id' => $recommendation]);
        $this->assertDatabaseHas('benefit_configurations', ['id' => $configuration->id, 'program_benefit_id' => $road->id]);

        $index = $this->actingAs($admin)->get(route('admin.programs.index'));
        $index->assertOk()->assertSeeTextInOrder([$road->name, 'Active'])->assertSeeText('Communal Irrigation Canal');

        $create = $this->get(route('admin.programs.create'));
        $create->assertOk();
        $entries = $create->viewData('programBenefits')->get($infrastructure->id)->keyBy('name');
        $this->assertFalse($entries[$road->name]['selectable']);
        $this->assertSame('Active', $entries[$road->name]['status']);
        $this->assertTrue($entries['Communal Irrigation Canal']['selectable']);
    }
}
