<?php

namespace App\Providers;

use App\Mail\GmailApiTransport;
use Aws\Sns\SnsClient;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SnsClient::class, fn () => new SnsClient([
            'version' => '2010-03-31',
            'region' => config('services.distribution_sms.region'),
            'retries' => 0,
            'http' => ['connect_timeout' => 3, 'timeout' => 8],
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('gmail-api', fn () => new GmailApiTransport());

        VerifyEmail::toMailUsing(fn ($user, string $url) => (new MailMessage())
            ->subject('FAMS | Verify your email address')
            ->markdown('emails.verify-email', [
                'verificationUrl' => $url,
                'expirationMinutes' => config('auth.verification.expire', 60),
            ]));

        \Illuminate\Pagination\Paginator::useBootstrapFive();
    }
}
