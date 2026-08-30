<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/farmer/profile');

        $response->assertOk();
    }

    public function test_farmer_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/farmer/profile', [
                'full_name' => 'Test Farmer',
                'address' => 'Bulan, Sorsogon',
                'barangay' => 'Zone 1',
                'contact_number' => '09123456789',
                'birthdate' => '2000-01-01',
                'crop_type' => 'Rice',
                'land_size' => 1.5,
            ]);

        $response
            ->assertSessionHasNoErrors();

        $response->assertRedirect();
    }
}