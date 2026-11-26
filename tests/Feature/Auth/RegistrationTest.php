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

    public function test_invalid_password_keeps_the_checked_rsbsa_record_on_the_account_step(): void
    {
        $this->seed(DemoRsbsaFarmerSeeder::class);

        $this->from(route('register'))->post(route('register'), [
            'rsbsa_number' => 'RSBSA-DEMO-0002',
            'email' => 'demo.registration@gmail.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('password');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('id="rsbsaStep"  style="display: none;"', false)
            ->assertDontSee('id="accountStep"  style="display: none;"', false)
            ->assertSee('Maria Demo Santos')
            ->assertSee('RSBSA-DEMO-0002')
            ->assertSee('demo.registration@gmail.com')
            ->assertDontSee('password123!');
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
            route('verification.notice')
        );

        $this->assertDatabaseHas('farmers', [
            'rsbsa_number' => 'RSBSA-DEMO-0020',
            'full_name' => 'Nina Demo Valdez',
            'rsbsa_status' => true,
        ]);

        Notification::assertSentTo(auth()->user(), \Illuminate\Auth\Notifications\VerifyEmail::class);
        $this->assertFalse(auth()->user()->hasVerifiedEmail());

        auth()->logout();

        $this->postJson('/register/check-rsbsa', ['rsbsa_number' => 'RSBSA-DEMO-0020'])
            ->assertConflict()
            ->assertExactJson([
                'success' => false,
                'message' => 'This RSBSA Number Is Already Registered',
            ]);

        $this->post('/register', [
            'rsbsa_number' => 'RSBSA-DEMO-0020',
            'email' => 'anotherdemo@gmail.com',
            'password' => 'Farmer123!',
            'password_confirmation' => 'Farmer123!',
        ])->assertSessionHasErrors([
            'rsbsa_number' => 'This RSBSA Number Is Already Registered',
        ]);

        Notification::assertCount(1);
    }
}