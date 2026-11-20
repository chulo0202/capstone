<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DemoRsbsaFarmerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();

        $this->seed(DemoRsbsaFarmerSeeder::class);

        $this->postJson('/register/check-rsbsa', ['rsbsa_number' => 'RSBSA-DEMO-0020'])
            ->assertOk()
            ->assertJsonPath('full_name', 'Nina Demo Valdez');

        $response = $this->post('/register', [
            'rsbsa_number' => 'RSBSA-DEMO-0020',
            'email' => 'testregistration@gmail.com',
            'password' => 'Farmer123!',
            'password_confirmation' => 'Farmer123!',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(
            route('farmer.dashboard', absolute: false)
        );

        $this->assertDatabaseHas('farmers', [
            'rsbsa_number' => 'RSBSA-DEMO-0020',
            'full_name' => 'Nina Demo Valdez',
            'rsbsa_status' => true,
        ]);

        auth()->logout();

        $this->post('/register', [
            'rsbsa_number' => 'RSBSA-DEMO-0020',
            'email' => 'anotherdemo@gmail.com',
            'password' => 'Farmer123!',
            'password_confirmation' => 'Farmer123!',
        ])->assertSessionHasErrors('rsbsa_number');

        Notification::assertNothingSent();
    }
}