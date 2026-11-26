<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $credentials = $request->only('email');

        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            return back()->withInput($credentials)->withErrors([
                'email' => 'Password reset email is temporarily unavailable. Please contact the MAO.',
            ]);
        }

        try {
            $status = Password::sendResetLink($credentials);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withInput($credentials)->withErrors([
                'email' => 'Password reset email could not be sent. Please try again shortly.',
            ]);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
