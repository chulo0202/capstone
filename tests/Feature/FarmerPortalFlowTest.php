<?php

namespace Tests\Feature;

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
            'profile_completed' => true,
        ]);

        $program = Program::create([
            'name' => 'Seed Subsidy',
            'description' => 'Support for rice farmers',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post("/farmer/applications/{$program->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'farmer_id' => $user->fresh()->farmer->id,
            'program_id' => $program->id,
            'status' => 'pending',
        ]);
    }
}
