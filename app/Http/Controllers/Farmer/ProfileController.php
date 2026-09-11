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
            'barangay' => [
                  'required',
                  'in:A. Bonifacio,Abad Santos,Aguinaldo,Antipolo,Beguin,Bical,Bonga,Butag,Cadandanan,Calomagon,Calpi,Cocok-Cabitan,Daganas,Danao,Dolos,E. Quirino,Fabrica,Gate,Benigno S. Aquino,Inararan,J. Gerona,J.P. Laurel,Jamorawon,Lajong,Libertad,Magsaysay,Managanaga,Marinab,Nasuje,Montecalvario,N. Roque,Namo,Obrero,Osmeña,Otavi,Padre Diaz,Palale,Quezon,R. Gerona,Recto,M. Roxas,Sagrada,San Francisco,San Isidro,San Juan Bag-o,San Juan Daan,San Rafael,San Ramon,San Vicente,Santa Remedios,Santa Teresita,Sigad,Somagongsong,G. Del Pilar,Taromata,Zone I Pob.,Zone II Pob.,Zone III Pob.,Zone IV Pob.,Zone V Pob.,Zone VI Pob.,Zone VII Pob.,Zone VIII Pob.',
                 ],
            'contact_number' => [
                  'required',
                  'regex:/^09\d{9}$/',
                  ],
            'birthdate' => 'required|date|before:today',
            'crop_type' => [
                 'required',
                 'in:Rice,Corn,Vegetables,Fruits,Coconut,Other',
                  ],
            'land_size' => 'required|numeric|min:0.01',

            'rsbsa_status' => ['required', 'accepted'],
            'rsbsa_number' => 'nullable|string|max:50',

            'association_membership' => 'boolean',
            'four_ps_membership' => 'boolean',
            'farmer_association' => 'nullable|string|max:255',

            'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'farmer_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $validated['rsbsa_status'] = $request->boolean('rsbsa_status');
        $validated['association_membership'] = $request->boolean('association_membership');
        $validated['four_ps_membership'] = $request->boolean('four_ps_membership');

        if ($request->hasFile('valid_id')) {
    $path = $request->file('valid_id')->store('valid-ids', 'private');
    $validated['valid_id_path'] = $path;
}

        if ($request->hasFile('farmer_photo')) {
            $path = $request->file('farmer_photo')->store('farmer-photos', 'public');
            $validated['farmer_photo_path'] = $path;
        }

        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
            $user->save();
        }

        unset($validated['valid_id']);
        unset($validated['farmer_photo']);
        unset($validated['password']);
        unset($validated['password_confirmation']);

        $farmer = Farmer::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($validated, ['profile_completed' => true])
        );

        try {
    $qrCodeService->generateForFarmer($farmer);
} catch (\Exception $e) {
    // QR generation failed
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
