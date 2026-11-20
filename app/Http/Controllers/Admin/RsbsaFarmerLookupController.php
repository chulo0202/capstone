<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\RsbsaFarmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsbsaFarmerLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['rsbsa_number' => ['required', 'string', 'max:50']]);

        $record = RsbsaFarmer::where('rsbsa_number', $validated['rsbsa_number'])->first();

        if (! $record) {
            return response()->json(['message' => 'RSBSA number not found. Check the number or enter the farmer details manually.'], 404);
        }

        if (Farmer::where('rsbsa_number', $record->rsbsa_number)->exists()) {
            return response()->json(['message' => 'A farmer is already registered with this RSBSA number.'], 409);
        }

        return response()->json($record->registrationDetails());
    }
}
