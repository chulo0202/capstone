<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FarmerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Farmer::with('user')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('barangay', 'like', "%{$search}%")
                    ->orWhere('crop_type', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('profile_completed', $status === 'complete');
        }

        return view('admin.farmers', [
            'farmers' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.farmers-create');
    }

    public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        // Account information
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',

        // Farmer information
        'full_name' => 'required|string|max:255',
        'address' => 'required|string|max:500',
        'barangay' => 'required|string|max:255',
        'contact_number' => 'required|string|max:20',
        'birthdate' => 'required|date',
        'crop_type' => 'required|string|max:255',
        'land_size' => 'required|numeric|min:0.01',

        // Eligibility information
        'rsbsa_number' => 'nullable|string|max:255',
        'rsbsa_status' => 'nullable|boolean',
        'association_membership' => 'nullable|boolean',
        'four_ps_membership' => 'nullable|boolean',
        'farmer_association' => 'nullable|string|max:255',

        // File uploads
        'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'farmer_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    // Create farmer's login account
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => 'farmer',
    ]);

    // Upload Valid ID
    $validIdPath = null;

    if ($request->hasFile('valid_id')) {
        $validIdPath = $request->file('valid_id')
            ->store('farmer-valid-ids', 'public');
    }

    // Upload Farmer Photo
    $farmerPhotoPath = null;

    if ($request->hasFile('farmer_photo')) {
        $farmerPhotoPath = $request->file('farmer_photo')
            ->store('farmer-photos', 'public');
    }

    // Create farmer profile
    $farmer = Farmer::create([
        'user_id' => $user->id,
        'full_name' => $validated['full_name'],
        'address' => $validated['address'],
        'barangay' => $validated['barangay'],
        'contact_number' => $validated['contact_number'],
        'birthdate' => $validated['birthdate'],
        'crop_type' => $validated['crop_type'],
        'land_size' => $validated['land_size'],

        'rsbsa_number' => $validated['rsbsa_number'] ?? null,
        'rsbsa_status' => $validated['rsbsa_status'] ?? false,
        'association_membership' => $validated['association_membership'] ?? false,
        'four_ps_membership' => $validated['four_ps_membership'] ?? false,
        'farmer_association' => $validated['farmer_association'] ?? null,

        'valid_id_path' => $validIdPath,
        'farmer_photo_path' => $farmerPhotoPath,

        'profile_completed' => true,
    ]);

    // Record admin action
    AuditLog::create([
        'user_id' => $request->user()->id,
        'action' => 'created',
        'model' => 'Farmer',
        'model_id' => $farmer->id,
        'details' => [
            'email' => $user->email,
            'full_name' => $farmer->full_name,
        ],
    ]);

    return redirect()
        ->route('admin.farmers.index')
        ->with('success', 'Farmer added successfully.');
}

    public function edit(Farmer $farmer): View
    {
        return view('admin.farmers-edit', compact('farmer'));
    }

    public function update(Request $request, Farmer $farmer): RedirectResponse
{
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'barangay' => 'required|string|max:255',
        'crop_type' => 'required|string|max:255',
        'contact_number' => 'nullable|string|max:20',
        'land_size' => 'nullable|numeric|min:0',
        'profile_completed' => 'required|boolean',

        // Farmer Photo
        'farmer_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    // Upload new farmer photo if provided
    if ($request->hasFile('farmer_photo')) {

        // Delete old photo if it exists
        if ($farmer->farmer_photo_path) {
            Storage::disk('public')->delete($farmer->farmer_photo_path);
        }

        // Store new photo
        $validated['farmer_photo_path'] = $request->file('farmer_photo')
            ->store('farmer-photos', 'public');
    }

    $farmer->update($validated);

    AuditLog::create([
        'user_id' => $request->user()->id,
        'action' => 'updated',
        'model' => 'Farmer',
        'model_id' => $farmer->id,
        'details' => $validated,
    ]);

    return redirect()
        ->route('admin.farmers.index')
        ->with('success', 'Farmer updated successfully.');
}

    public function verify(Farmer $farmer, Request $request): RedirectResponse
    {
        $farmer->update(['profile_completed' => true]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'verified',
            'model' => 'Farmer',
            'model_id' => $farmer->id,
            'details' => ['verified' => true],
        ]);

        return back()->with('success', 'Farmer verified.');
    }

    public function show(Farmer $farmer): View
    {
        $farmer->load(['user', 'recommendations.program', 'distributions.program']);

        return view('admin.farmers-show', compact('farmer'));
    }

    public function destroy(Farmer $farmer, Request $request): RedirectResponse
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'model' => 'Farmer',
            'model_id' => $farmer->id,
            'details' => ['name' => $farmer->full_name],
        ]);

        $farmer->user?->delete();

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer record deleted successfully.');
    }
}
