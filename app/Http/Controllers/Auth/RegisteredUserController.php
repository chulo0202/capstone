<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\RsbsaFarmer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rsbsa_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Farmer::class, 'rsbsa_number'),
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
                Rules\Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'rsbsa_number.unique' => 'This RSBSA Number Is Already Registered',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Verify RSBSA Record
        |--------------------------------------------------------------------------
        */

        $rsbsaFarmer = RsbsaFarmer::where(
            'rsbsa_number',
            trim($validated['rsbsa_number'])
        )->first();

        if (! $rsbsaFarmer) {
            throw ValidationException::withMessages([
                'rsbsa_number' => 'RSBSA number not found. Please verify your RSBSA number or contact the MAO.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create User and Farmer Profile
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($validated, $rsbsaFarmer, &$user) {

            $user = User::create([
                'name' => $rsbsaFarmer->full_name,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'farmer',
            ]);

            Farmer::create([
                'user_id' => $user->id,
                'full_name' => $rsbsaFarmer->full_name,
                'address' => '',
                'barangay' => $rsbsaFarmer->barangay,
                'contact_number' => '',
                'birthdate' => now()->subYears(18)->format('Y-m-d'),
                'crop_type' => $rsbsaFarmer->crop_type,
                'land_size' => $rsbsaFarmer->land_size,
                'rsbsa_status' => true,
                'rsbsa_number' => $rsbsaFarmer->rsbsa_number,
                'association_membership' => false,
                'profile_completed' => false,
            ]);
        });

        Auth::login($user);

        return redirect()->route('farmer.dashboard');
    }
}