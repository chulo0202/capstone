<?php

namespace Tests\Feature\Auth;

use App\Models\RsbsaFarmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        RsbsaFarmer::create([
            'rsbsa_number' => 'RSBSA-TEST-REG-001',
            'full_name' => 'Test User',
            'barangay' => 'Gate',
            'crop_type' => 'Rice',
            'land_size' => 2.50,
        ]);

        $response = $this->post('/register', [
            'rsbsa_number' => 'RSBSA-TEST-REG-001',
            'email' => 'testregistration@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(
            route('verification.notice', absolute: false)
        );
    }
}