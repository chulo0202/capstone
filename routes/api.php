<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QRVerificationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! auth()->attempt($credentials)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.',
        ], 401);
    }

    $user = $request->user();

    if ($user->isFarmer() && $user->farmer?->is_active !== true) {
        auth()->logout();

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password.',
        ], 401);
    }

    $token = $user->createToken('fams-mobile')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login successful.',
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ],
    ]);
})->middleware('throttle:5,1');

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logout successful.',
    ]);
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| FAMS QR API
|--------------------------------------------------------------------------
*/

Route::post('/qr/verify', [
    QRVerificationController::class,
    'verify'
])->middleware(['auth:sanctum', 'admin', 'throttle:10,1']);

Route::post('/qr/release', [
    QRVerificationController::class,
    'release'
])->middleware(['auth:sanctum', 'admin', 'throttle:10,1']);
