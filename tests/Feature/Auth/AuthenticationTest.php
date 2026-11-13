<?php

namespace Tests\Feature\Auth;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }


    public function test_farmer_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(route('farmer.dashboard', absolute: false));
    }

    public function test_inactive_farmer_cannot_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user, false);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_farmer_without_a_farmer_record_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();

        $response->assertRedirect('/');
    }

    public function test_active_farmer_can_authenticate_through_the_api(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token']);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_inactive_farmer_cannot_authenticate_through_the_api(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);
        $this->createFarmer($user, false);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password.',
            ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_farmer_without_a_farmer_record_cannot_authenticate_through_the_api(): void
    {
        $user = User::factory()->create([
            'role' => 'farmer',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password.',
            ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_api_login_remains_unaffected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token']);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
        ]);
    }

    private function createFarmer(User $user, bool $isActive = true): Farmer
    {
        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-AUTH-' . fake()->unique()->numerify('######'),
            'profile_completed' => true,
        ]);

        if (! $isActive) {
            $farmer->is_active = false;
            $farmer->save();
        }

        return $farmer;
    }
}
