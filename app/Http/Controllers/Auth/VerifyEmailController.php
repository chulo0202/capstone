<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isFarmer()) {
            // Match the email again when writing so a concurrent email change cannot be verified by an old link.
            if (! $user->hasVerifiedEmail() && $user->newQuery()->whereKey($user->id)
                ->where('email', $user->email)->whereNull('email_verified_at')
                ->update(['email_verified_at' => now()])) {
                event(new Verified($user->refresh()));
            }

            return to_route('farmer.profile');
        }

        if ($user->hasVerifiedEmail()) {
            if (
                $request->user()->role === 'admin'
                && $request->user()->admin_registration_status === 'pending'
            ) {
                return redirect()
                    ->route('verification.notice')
                    ->with('status', 'admin-registration-pending');
            }

            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        if (
            $request->user()->role === 'admin'
            && $request->user()->admin_registration_status === 'pending'
        ) {
            return redirect()
                ->route('verification.notice')
                ->with('status', 'admin-registration-pending');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
