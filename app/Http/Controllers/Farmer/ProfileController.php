<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Services\QrCodeService;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(): View
    {
        $farmer = Auth::user()->farmer;

        return view('farmer.profile', compact('farmer'));
    }

    public function update(Request $request, QrCodeService $qrCodeService, RecommendationService $recommendationService): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'barangay' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'birthdate' => 'required|date|before:today',
            'crop_type' => 'required|string|max:255',
            'land_size' => 'required|numeric|min:0',
            'rsbsa_status' => 'boolean',
            'rsbsa_number' => 'nullable|string|max:50',
            'association_membership' => 'boolean',
            'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $validated['rsbsa_status'] = $request->boolean('rsbsa_status');
        $validated['association_membership'] = $request->boolean('association_membership');

        if ($request->hasFile('valid_id')) {
            $path = $request->file('valid_id')->store('valid-ids', 'public');
            $validated['valid_id_path'] = $path;
        }

        unset($validated['valid_id']);

        $farmer = Farmer::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['profile_completed' => true])
        );

        if (! $farmer->qr_code_path) {
            try {
                $qrCodeService->generateForFarmer($farmer);
            } catch (\Exception $e) {
                // QR generation requires GD extension
            }
        }

        $recommendationService->generateForFarmer($farmer->fresh());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function qrCode(): View|RedirectResponse
    {
        $farmer = Auth::user()->farmer;

        if (! $farmer || ! $farmer->profile_completed) {
            return redirect()->route('farmer.profile')
                ->with('error', 'Please complete your profile first.');
        }

        return view('farmer.qr-code', compact('farmer'));
    }

    public function downloadQr(): StreamedResponse|RedirectResponse
    {
        $farmer = Auth::user()->farmer;

        if (! $farmer?->qr_code_path || ! Storage::disk('public')->exists($farmer->qr_code_path)) {
            return back()->with('error', 'QR code not available.');
        }

        return Storage::disk('public')->download(
            $farmer->qr_code_path,
            'fams-qr-'.$farmer->id.'.png'
        );
    }
}
