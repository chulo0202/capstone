<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'full_name' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'crop_type' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'farmer',
        ]);

        $farmer = Farmer::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'address' => 'Pending update',
            'barangay' => $validated['barangay'],
            'contact_number' => $validated['contact_number'] ?? '',
            'birthdate' => now()->subYears(25)->format('Y-m-d'),
            'crop_type' => $validated['crop_type'],
            'land_size' => 0,
            'profile_completed' => false,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'model' => 'Farmer',
            'model_id' => $farmer->id,
            'details' => ['email' => $user->email],
        ]);

        return redirect()->route('admin.farmers.index')->with('success', 'Farmer added successfully.');
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
            'rsbsa_status' => 'boolean',
            'association_membership' => 'boolean',
        ]);

        $farmer->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'model' => 'Farmer',
            'model_id' => $farmer->id,
            'details' => $validated,
        ]);

        return redirect()->route('admin.farmers.index')->with('success', 'Farmer updated successfully.');
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
