<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_gmail_api_password_reset_reports_success_only_after_acceptance(): void
    {
        config([
            'mail.default' => 'gmail-api',
            'services.distribution_gmail.sender' => 'fams@example.com',
            'services.distribution_gmail.client_id' => 'test-client',
            'services.distribution_gmail.client_secret' => 'test-secret',
            'services.distribution_gmail.refresh_token' => 'test-refresh-token',
        ]);
        $sendUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            $sendUrl => Http::sequence()->push(['id' => 'gmail-message'])->pushStatus(503),
        ]);

        $recipient = User::factory()->create();
        $this->post('/forgot-password', ['email' => $recipient->email])
            ->assertSessionHas('status', __(\Illuminate\Support\Facades\Password::RESET_LINK_SENT));
        Http::assertSent(function ($request) use ($sendUrl, $recipient) {
            if ($request->url() !== $sendUrl) {
                return false;
            }

            $message = base64_decode(strtr($request['raw'], '-_', '+/'));

            return str_contains($message, 'From: FAMS <fams@example.com>')
                && str_contains($message, $recipient->email)
                && str_contains($message, '/reset-password/');
        });

        $secondRecipient = User::factory()->create();
        $this->post('/forgot-password', ['email' => $secondRecipient->email])
            ->assertSessionHasErrors(['email' => 'Password reset email could not be sent. Please try again shortly.'])
            ->assertSessionMissing('status');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
