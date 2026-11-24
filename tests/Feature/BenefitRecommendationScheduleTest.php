<?php

namespace Tests\Feature;

use App\Models\Farmer;
use App\Models\Program;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BenefitRecommendationScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_benefit_appears_in_recommendations(): void
    {
        [$user, $benefit] = $this->createScenario();

        $benefit->configurations()->create([
            'description' => 'Active configuration',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        $benefit->program->update(['start_at' => now()->addDays(2)]);

        $this->actingAs($user)
            ->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertSeeText($benefit->name)
            ->assertSeeText(now()->subDay()->format('M j, Y').' – '.now()->addDay()->format('M j, Y'));
    }

    public function test_upcoming_benefit_is_hidden_from_recommendations(): void
    {
        [$user, $benefit] = $this->createScenario();

        $benefit->configurations()->create([
            'description' => 'Upcoming configuration',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(10),
        ]);

        $this->actingAs($user)
            ->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertDontSeeText($benefit->name);

        $this->get(route('farmer.eligibility'))
            ->assertRedirect(route('farmer.recommendations'));
    }

    public function test_expired_benefit_is_hidden_from_recommendations(): void
    {
        [$user, $benefit] = $this->createScenario();

        $benefit->configurations()->create([
            'description' => 'Expired configuration',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertDontSeeText($benefit->name);

        $this->get(route('farmer.eligibility'))
            ->assertRedirect(route('farmer.recommendations'));
    }

    public function test_inactive_parent_blocks_recommendations_for_current_configuration(): void
    {
        [$user, $benefit] = $this->createScenario();
        $benefit->program->update(['is_active' => false]);
        $benefit->configurations()->create([
            'description' => 'Current configuration',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        app(RecommendationService::class)->generateForFarmer($user->farmer);
        $this->assertDatabaseMissing('recommendations', ['program_benefit_id' => $benefit->id]);

        $this->actingAs($user)
            ->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertDontSeeText($benefit->name);

        $this->get(route('farmer.eligibility'))
            ->assertRedirect(route('farmer.recommendations'));
    }

    public function test_pcic_new_application_and_claim_are_recommended_year_round(): void
    {
        [$user] = $this->createScenario();

        $pcic = Program::create([
            'name' => 'PCIC Crop Insurance',
            'description' => 'PCIC benefits',
            'is_active' => false,
        ]);
        $claim = $pcic->benefits()->create(['name' => 'Claim for Indemnity', 'always_active' => true]);
        $newApplication = $pcic->benefits()->create(['name' => 'New Application']);
        $legacy = $pcic->benefits()->create(['name' => 'Legacy PCIC Benefit', 'always_active' => true]);

        app(RecommendationService::class)->generateForFarmer($user->farmer);
        $this->assertDatabaseHas('recommendations', ['program_benefit_id' => $claim->id]);
        $this->assertDatabaseHas('recommendations', ['program_benefit_id' => $newApplication->id]);

        $response = $this->actingAs($user)->get(route('farmer.recommendations'))->assertOk();
        $this->assertEqualsCanonicalizing([$claim->id, $newApplication->id], $response->viewData('recommendations')->where('program.id', $pcic->id)->pluck('benefit.id')->all());
        $response->assertSeeText('Open year-round');

        $admin = User::factory()->create(['role' => 'admin', 'admin_registration_status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.programs.index', ['status' => 'active']))->assertOk()
            ->assertSeeText('PCIC Crop Insurance')->assertSeeText('Currently Active')
            ->assertSeeText('Open year-round')->assertSeeText('No end date');
        $this->get(route('admin.programs.index', ['status' => 'inactive']))->assertOk()
            ->assertDontSeeText('PCIC Crop Insurance');
        $response->assertDontSeeText($legacy->name);
    }

    public function test_service_request_requires_all_eligibility_requirements(): void
    {
        [$user, $benefit] = $this->createScenario();
        $program = $benefit->program;
        $program->update(['name' => 'Equipment and Machinery Support']);
        $benefit->eligibilityRules()->create([
            'program_id' => $program->id,
            'crop_types' => ['Rice'],
            'requires_association' => true,
        ]);

        $this->actingAs($user)->get(route('farmer.recommendations'))
            ->assertOk()
            ->assertSeeText('Complete missing requirements to apply.')
            ->assertDontSee('data-benefit-id="'.$benefit->id.'"', false);

        $data = [
            'program_benefit_id' => $benefit->id,
            'service_description' => 'Requesting a combine harvester for the rice field.',
            'preferred_service_date' => now()->addDay()->toDateString(),
        ];

        $this->post(route('farmer.applications.store', $program), $data)
            ->assertSessionHas('error', 'You cannot apply for this benefit yet. Missing requirements: Farmers association membership required.');
        $this->postJson(route('farmer.applications.store', $program), $data)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You cannot apply for this benefit yet. Missing requirements: Farmers association membership required.');
        $this->assertDatabaseCount('applications', 0);

        $user->farmer->update(['association_membership' => true]);

        $this->get(route('farmer.recommendations'))
            ->assertSee('data-benefit-id="'.$benefit->id.'"', false);

        $this->postJson(route('farmer.applications.store', $program), ['program_benefit_id' => $benefit->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_description', 'preferred_service_date']);

        $this->postJson(route('farmer.applications.store', $program), $data)
            ->assertOk()
            ->assertJsonPath('redirect', route('farmer.recommendations'))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_all_benefit_form_types_return_field_errors_for_the_modal(): void
    {
        foreach ([
            ['PCIC Crop Insurance', 'Claim for Indemnity', ['date_of_loss', 'cause_of_loss', 'area_damaged', 'damage_photo']],
            ['PCIC Crop Insurance', 'New Application', ['variety_planted', 'area_to_insure', 'expected_harvest_date']],
            ['Agricultural Loans', 'Loan', ['loan_amount', 'project_description', 'privacy_consent']],
            ['Seed Subsidies', 'Seeds', ['confirm_intent']],
            ['Fertilizer Subsidies', 'Fertilizer', ['confirm_intent']],
        ] as [$programName, $benefitName, $required]) {
            [$user, $benefit] = $this->createScenario();
            $benefit->program->update(['name' => $programName]);
            $benefit->update(['name' => $benefitName]);

            $this->actingAs($user)->postJson(route('farmer.applications.store', $benefit->program), [
                'program_benefit_id' => $benefit->id,
            ])->assertUnprocessable()->assertJsonValidationErrors($required);
        }

        $this->assertDatabaseCount('applications', 0);
    }

    private function createScenario(): array
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Schedule Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-SCHEDULE-' . uniqid(),
            'profile_completed' => true,
            'is_active' => true,
        ]);

        $farmer->is_active = true;
        $farmer->save();

        $program = Program::create([
            'name' => 'Schedule Test Program',
            'description' => 'Benefit schedule testing',
            'is_active' => true,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(20),
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Schedule Test Benefit',
            'description' => 'Test benefit',
            ]);

        $benefit->eligibility_configured_at = now();
        $benefit->save();

        return [$user, $benefit];
    }
}
