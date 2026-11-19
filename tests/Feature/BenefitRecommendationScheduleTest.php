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
            ->assertSeeText($benefit->name);
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
            ->assertOk()
            ->assertDontSeeText($benefit->name);
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
            ->assertOk()
            ->assertDontSeeText($benefit->name);
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
            ->assertOk()
            ->assertDontSeeText($benefit->name);
    }

    public function test_only_the_always_active_pcic_claim_is_recommended_when_pcic_is_inactive(): void
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
        $this->assertDatabaseMissing('recommendations', ['program_benefit_id' => $newApplication->id]);

        $response = $this->actingAs($user)->get(route('farmer.recommendations'))->assertOk();
        $this->assertSame([$claim->id], $response->viewData('recommendations')->where('program.id', $pcic->id)->pluck('benefit.id')->all());
        $response->assertDontSeeText($legacy->name);
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
