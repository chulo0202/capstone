<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RsbsaFarmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsbsaRegistrationController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rsbsa_number' => [
                'required',
                'string',
                'max:50',
            ],
        ]);

        $rsbsaFarmer = RsbsaFarmer::where(
            'rsbsa_number',
            trim($validated['rsbsa_number'])
        )->first();

        if (! $rsbsaFarmer) {
            return response()->json([
                'success' => false,
                'message' => 'RSBSA number not found. Please verify your RSBSA number or contact the MAO.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'RSBSA record found.',
            'rsbsa_number' => $rsbsaFarmer->rsbsa_number,
            'full_name' => $rsbsaFarmer->full_name,
            'barangay' => $rsbsaFarmer->barangay,
            'crop_type' => $rsbsaFarmer->crop_type,
            'land_size' => $rsbsaFarmer->land_size,
        ]);
    }
}