<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AdminRegistrationController extends Controller
{
    /**
     * Display the admin registration form.
     */
    public function create(): View
    {
        return view('auth.admin-register');
    }

    /**
     * Handle an incoming admin registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'regex:/^[a-z0-9._%+-]+@gmail\.com$/',
                'max:255',
                'unique:' . User::class,
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'admin_registration_status' => 'pending',
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Admin registration submitted successfully. Please wait for approval from an existing administrator.'
            );
    }
}