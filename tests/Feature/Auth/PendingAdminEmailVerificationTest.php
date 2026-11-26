<?php

namespace Tests\Feature\Auth;

use App\Models\Farmer;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PendingAdminEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_admin_login_redirects_to_verification_notice(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_pending_admin_can_access_verification_notice(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email');
    }

    public function test_verified_pending_admin_sees_approval_notice_without_dashboard_redirect(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email')
            ->assertSee('Administrator approval is still required');
    }

    public function test_pending_admin_cannot_access_protected_admin_routes(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_verified_pending_admin_cannot_access_admin_dashboard_before_approval(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_pending_admin_cannot_access_protected_farmer_routes(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('farmer.dashboard'))
            ->assertForbidden();
    }

    public function test_unverified_pending_admin_can_request_verification_email(): void
    {
        Notification::fake();

        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('verification.send'));

        $response->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($admin, VerifyEmail::class);
    }

    public function test_verified_pending_admin_does_not_receive_verification_email_on_resend(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('verification.send'));

        $response->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'admin-registration-pending');
        Notification::assertNothingSent();
    }

    public function test_signed_verification_link_verifies_pending_admin_and_returns_to_notice(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $response = $this->actingAs($admin)->get($verificationUrl);

        $this->assertTrue($admin->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'admin-registration-pending');
    }

    public function test_approved_admin_login_redirect_remains_unchanged(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_unverified_farmer_can_log_in_and_access_dashboard(): void
    {
        $farmer = User::factory()->unverified()->create([
            'role' => 'farmer',
            'admin_registration_status' => 'approved',
        ]);
        $this->createFarmerProfile($farmer);

        $loginResponse = $this->post('/login', [
            'email' => $farmer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($farmer);
        $loginResponse->assertRedirect(route('farmer.dashboard'));

        Notification::fake();

        $this->get(route('verification.notice'))->assertOk()->assertSee('Continue to My Profile');
        $this->from(route('farmer.profile'))->post(route('verification.send'))
            ->assertRedirect(route('farmer.profile'))
            ->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($farmer, VerifyEmail::class);
        $this->get(route('farmer.profile'))->assertOk()->assertSee('Verify Email');

        $this->get(route('farmer.dashboard'))->assertOk();
        $this->assertFalse($farmer->fresh()->hasVerifiedEmail());
    }

    public function test_farmer_email_change_requires_reverification_and_invalidates_the_old_link(): void
    {
        Notification::fake();
        $farmer = User::factory()->create(['role' => 'farmer', 'email' => 'old-address@gmail.com']);
        $this->createFarmerProfile($farmer);
        $verificationLink = fn (string $email) => URL::temporarySignedRoute(
            'verification.verify', now()->addHour(), ['id' => $farmer->id, 'hash' => sha1($email)]
        );
        $oldLink = $verificationLink($farmer->email);
        $this->actingAs($farmer)->patch(route('farmer.profile.email.update'), [
            'email' => 'new-address@gmail.com',
            'current_password' => 'password',
        ])->assertRedirect(route('farmer.profile'))->assertSessionHas('status', 'verification-link-sent');

        $this->assertSame('new-address@gmail.com', $farmer->fresh()->email);
        $this->assertFalse($farmer->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($farmer, VerifyEmail::class);
        $this->get($oldLink)->assertForbidden();
        $this->get($verificationLink('new-address@gmail.com'))->assertRedirect(route('farmer.profile'));
        $this->assertTrue($farmer->fresh()->hasVerifiedEmail());
    }

    private function createFarmerProfile(User $farmer): void
    {
        Farmer::create([
            'user_id' => $farmer->id,
            'full_name' => 'Test Farmer',
            'address' => 'Sample Address',
            'barangay' => 'Bulan',
            'contact_number' => '09123456789',
            'birthdate' => '1990-01-01',
            'crop_type' => 'Rice',
            'land_size' => 1.5,
            'rsbsa_number' => 'RSBSA-TEST-VERIFY-' . fake()->unique()->numerify('######'),
            'profile_completed' => true,
        ]);
    }
}
