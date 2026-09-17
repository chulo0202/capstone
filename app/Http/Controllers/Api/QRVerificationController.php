<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QRVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $farmer = Farmer::where(
            'qr_code_token',
            $validated['token']
        )->first();

        if (! $farmer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or farmer not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Farmer verified successfully.',
            'farmer' => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'barangay' => $farmer->barangay,
                'crop_type' => $farmer->crop_type,
                'land_size' => $farmer->land_size,
            ],
        ]);
    }
}