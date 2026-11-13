<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerPortalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_apply_for_a_program_from_recommendations(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-PORTAL-001',
            'profile_completed' => true,
        ]);

        $program = Program::create([
            'name' => 'Seed Subsidy',
            'description' => 'Support for rice farmers',
            'is_active' => true,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
        $benefit = $program->benefits()->create(['name' => 'Seed package']);
        $benefit->eligibility_configured_at = now();
        $benefit->save();

        $response = $this->actingAs($user)->post("/farmer/applications/{$program->id}", [
            'program_benefit_id' => $benefit->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'farmer_id' => $user->fresh()->farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'pending',
        ]);
    }

    public function test_inactive_farmer_cannot_submit_a_new_application(): void
    {
        [$user, $farmer, $program, $benefit] = $this->createFarmerAndActiveProgram();
        $farmer->is_active = false;
        $farmer->save();

        $this->actingAs($user)
            ->post("/farmer/applications/{$program->id}", [
                'program_benefit_id' => $benefit->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('applications', [
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
        ]);
    }

    public function test_authenticated_inactive_farmer_cannot_access_farmer_portal_routes(): void
    {
        [$user, $farmer] = $this->createFarmerAndActiveProgram();
        $farmer->is_active = false;
        $farmer->save();

        $this->actingAs($user)
            ->get(route('farmer.dashboard'))
            ->assertForbidden();
    }

    public function test_pending_application_shows_already_applied_button(): void
    {
        [$user, $farmer, $program, $benefit] = $this->createFarmerAndActiveProgram();

        Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/farmer/recommendations');

        $response->assertOk();
        $response->assertSee('Already applied');
        $response->assertDontSee('Apply Now');
    }

    public function test_rejected_application_shows_rejected_button(): void
    {
        [$user, $farmer, $program, $benefit] = $this->createFarmerAndActiveProgram();

        Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($user)->get('/farmer/recommendations');

        $response->assertOk();
        $response->assertSee('Rejected');
        $response->assertDontSee('Apply Now');
    }

    public function test_farmer_cannot_submit_duplicate_application(): void
    {
        [$user, $farmer, $program, $benefit] = $this->createFarmerAndActiveProgram();

        Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->post("/farmer/applications/{$program->id}", [
                'program_benefit_id' => $benefit->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            'info',
            'You already applied for this benefit.'
        );

        $this->assertDatabaseCount('applications', 1);
    }

    public function test_approved_application_shows_already_applied_button(): void
    {
        [$user, $farmer, $program, $benefit] = $this->createFarmerAndActiveProgram();

        Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'program_benefit_id' => $benefit->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get('/farmer/recommendations');

        $response->assertOk();
        $response->assertSee($program->name);
        $response->assertSee('Already applied');
        $response->assertDontSee('Apply Now');
    }

    private function createFarmerAndActiveProgram(): array
    {
        $user = User::factory()->create(['role' => 'farmer']);

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-PORTAL-' . uniqid(),
            'profile_completed' => true,
        ]);

        $program = Program::create([
            'name' => 'Test Active Program',
            'description' => 'Program for application status tests',
            'is_active' => true,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
        $benefit = $program->benefits()->create(['name' => 'Test benefit']);
        $benefit->eligibility_configured_at = now();
        $benefit->save();

        return [$user, $farmer, $program, $benefit];
    }
}
