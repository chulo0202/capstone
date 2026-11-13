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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FarmerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Farmer::with('user')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('full_name', "%{$search}%")
                    ->orWhereLike('barangay', "%{$search}%")
                    ->orWhereLike('crop_type', "%{$search}%");
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

    /**
     * Store a new farmer.
     */
    public function store(Request $request): RedirectResponse
    {
        $isAssociationMember = $request->boolean('association_membership');

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
            'rsbsa_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('farmers', 'rsbsa_number'),
            ],
            'rsbsa_status' => 'nullable|boolean',
            'association_membership' => 'nullable|boolean',
            'four_ps_membership' => 'nullable|boolean',

            'farmer_association' => $isAssociationMember
                ? [
                    'required',
                    'string',
                    'max:255',
                    Rule::in(config('farmer_associations', [])),
                ]
                : 'nullable',

            // Documents
            'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'farmer_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $user = null;
        $validIdPath = null;
        $farmerPhotoPath = null;

        try {
            DB::transaction(function () use (
                $request,
                $validated,
                $isAssociationMember,
                &$user,
                &$validIdPath,
                &$farmerPhotoPath
            ) {
                // Create farmer login account.
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'farmer',
                ]);

                // Upload Valid ID to private storage.
                if ($request->hasFile('valid_id')) {
                    $validIdPath = $request->file('valid_id')
                        ->store('farmer-valid-ids', 'private');

                    if (! $validIdPath) {
                        throw new \RuntimeException('Valid ID upload failed.');
                    }
                }

                // Upload Farmer Photo to public storage.
                if ($request->hasFile('farmer_photo')) {
                    $farmerPhotoPath = $request->file('farmer_photo')
                        ->store('farmer-photos', 'public');

                    if (! $farmerPhotoPath) {
                        throw new \RuntimeException('Farmer photo upload failed.');
                    }
                }

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
                    'rsbsa_status' => $request->boolean('rsbsa_status'),
                    'association_membership' => $isAssociationMember,
                    'four_ps_membership' => $request->boolean('four_ps_membership'),

                    'farmer_association' => $isAssociationMember
                        ? $validated['farmer_association']
                        : null,

                    'valid_id_path' => $validIdPath,
                    'farmer_photo_path' => $farmerPhotoPath,
                    'profile_completed' => true,
                ]);

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
            });
        } catch (\Throwable $exception) {
            // Remove uploaded files if database transaction fails.
            if ($validIdPath) {
                try {
                    if (! Storage::disk('private')->delete($validIdPath)) {
                        Log::warning('Failed to clean up uploaded farmer Valid ID after creation failure.', [
                            'disk' => 'private',
                            'user_id' => $user?->id,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::error('Failed to clean up uploaded farmer Valid ID after creation failure.', [
                        'disk' => 'private',
                        'user_id' => $user?->id,
                        'exception' => get_class($cleanupException),
                    ]);
                }
            }

            if ($farmerPhotoPath) {
                try {
                    if (! Storage::disk('public')->delete($farmerPhotoPath)) {
                        Log::warning('Failed to clean up uploaded farmer photo after creation failure.', [
                            'disk' => 'public',
                            'user_id' => $user?->id,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::error('Failed to clean up uploaded farmer photo after creation failure.', [
                        'disk' => 'public',
                        'user_id' => $user?->id,
                        'exception' => get_class($cleanupException),
                    ]);
                }
            }

            Log::error('Failed to create farmer.', [
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return redirect()
            ->route('admin.farmers.index')
            ->with('success', 'Farmer added successfully.');
    }

    public function edit(Farmer $farmer): View
    {
        $farmer->loadMissing('user');

        return view('admin.farmers-edit', compact('farmer'));
    }

    /**
     * Update an existing farmer.
     */

public function update(Request $request, Farmer $farmer): RedirectResponse
{
    $isAssociationMember = $request->boolean('association_membership');

    $validated = $request->validate([
        // Farmer information
        'address' => 'required|string|max:500',
        'barangay' => 'required|string|max:255',
        'contact_number' => 'required|string|max:20',
        'birthdate' => 'required|date',
        'land_size' => 'required|numeric|min:0.01',

        // Eligibility information
        'association_membership' => 'nullable|boolean',
        'four_ps_membership' => 'nullable|boolean',

        'farmer_association' => $isAssociationMember
            ? [
                'required',
                'string',
                'max:255',
                Rule::in(config('farmer_associations', [])),
            ]
            : 'nullable',

        'profile_completed' => 'required|boolean',

        // Documents
        'valid_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        'farmer_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
    ]);

    $farmerData = [
        'address' => $validated['address'],
        'barangay' => $validated['barangay'],
        'contact_number' => $validated['contact_number'],
        'birthdate' => $validated['birthdate'],
        'land_size' => $validated['land_size'],

        'association_membership' => $isAssociationMember,
        'four_ps_membership' => $request->boolean('four_ps_membership'),

        'farmer_association' => $isAssociationMember
            ? $validated['farmer_association']
            : null,

        'profile_completed' => $validated['profile_completed'],
    ];

    $newValidIdPath = null;
    $newFarmerPhotoPath = null;

    // Save the old file paths before replacing them.
    $oldValidIdPath = $farmer->valid_id_path;
    $oldFarmerPhotoPath = $farmer->farmer_photo_path;

    try {
        // Upload new Valid ID to private storage.
        if ($request->hasFile('valid_id')) {
            $newValidIdPath = $request->file('valid_id')
                ->store('farmer-valid-ids', 'private');

            if (! $newValidIdPath) {
                throw new \RuntimeException('Valid ID upload failed.');
            }

            $farmerData['valid_id_path'] = $newValidIdPath;
        }

        // Upload new Farmer Photo to public storage.
        if ($request->hasFile('farmer_photo')) {
            $newFarmerPhotoPath = $request->file('farmer_photo')
                ->store('farmer-photos', 'public');

            if (! $newFarmerPhotoPath) {
                throw new \RuntimeException('Farmer photo upload failed.');
            }

            $farmerData['farmer_photo_path'] = $newFarmerPhotoPath;
        }

        // Update farmer and audit log together.
        DB::transaction(function () use ($request, $farmer, $farmerData) {
            $farmer->update($farmerData);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'model' => 'Farmer',
                'model_id' => $farmer->id,
                'details' => $farmerData,
            ]);
        });
    } catch (\Throwable $exception) {
        // Remove newly uploaded files if the update fails.
        if ($newValidIdPath) {
            try {
                if (! Storage::disk('private')->delete($newValidIdPath)) {
                    Log::warning('Failed to clean up uploaded farmer Valid ID after update failure.', [
                        'disk' => 'private',
                        'farmer_id' => $farmer->id,
                    ]);
                }
            } catch (\Throwable $cleanupException) {
                Log::error('Failed to clean up uploaded farmer Valid ID after update failure.', [
                    'disk' => 'private',
                    'farmer_id' => $farmer->id,
                    'exception' => get_class($cleanupException),
                ]);
            }
        }

        if ($newFarmerPhotoPath) {
            try {
                if (! Storage::disk('public')->delete($newFarmerPhotoPath)) {
                    Log::warning('Failed to clean up uploaded farmer photo after update failure.', [
                        'disk' => 'public',
                        'farmer_id' => $farmer->id,
                    ]);
                }
            } catch (\Throwable $cleanupException) {
                Log::error('Failed to clean up uploaded farmer photo after update failure.', [
                    'disk' => 'public',
                    'farmer_id' => $farmer->id,
                    'exception' => get_class($cleanupException),
                ]);
            }
        }

        Log::error('Failed to update farmer.', [
            'farmer_id' => $farmer->id,
            'error' => $exception->getMessage(),
        ]);

        throw $exception;
    }

    // Delete old files only after a successful database update.
    // Cleanup failures must not delete the newly saved files.
    try {
        if ($newValidIdPath && $oldValidIdPath) {
            Storage::disk('private')->delete($oldValidIdPath);
        }
    } catch (\Throwable $exception) {
        Log::warning('Failed to delete old farmer Valid ID.', [
            'farmer_id' => $farmer->id,
            'error' => $exception->getMessage(),
        ]);
    }

    try {
        if ($newFarmerPhotoPath && $oldFarmerPhotoPath) {
            Storage::disk('public')->delete($oldFarmerPhotoPath);
        }
    } catch (\Throwable $exception) {
        Log::warning('Failed to delete old farmer photo.', [
            'farmer_id' => $farmer->id,
            'error' => $exception->getMessage(),
        ]);
    }

    return redirect()
        ->route('admin.farmers.index')
        ->with('success', 'Farmer updated successfully.');
}


    public function verify(Farmer $farmer, Request $request): RedirectResponse
    {
        $farmer->update([
            'profile_completed' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'verified',
            'model' => 'Farmer',
            'model_id' => $farmer->id,
            'details' => [
                'verified' => true,
            ],
        ]);

        return back()->with('success', 'Farmer verified.');
    }

    public function show(Farmer $farmer): View
    {
        $farmer->load([
            'user',
            'recommendations.program',
            'distributions.program',
        ]);

        return view('admin.farmers-show', compact('farmer'));
    }

    public function deactivate(
        Farmer $farmer,
        Request $request
    ): RedirectResponse {
        $deactivated = DB::transaction(function () use ($farmer, $request): bool {
            $farmer = Farmer::query()
                ->lockForUpdate()
                ->findOrFail($farmer->id);

            $previousState = (bool) $farmer->is_active;

            if (! $previousState) {
                return false;
            }

            $farmer->is_active = false;
            $farmer->save();

            $user = $farmer->user;
            if ($user) {
                $user->tokens()->delete();
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'deactivated',
                'model' => 'Farmer',
                'model_id' => $farmer->id,
                'details' => [
                    'previous_is_active' => $previousState,
                    'new_is_active' => false,
                ],
            ]);

            return true;
        });

        if (! $deactivated) {
            return back()->with('info', 'Farmer is already inactive.');
        }

        return redirect()
            ->route('admin.farmers.index')
            ->with('success', 'Farmer deactivated successfully.');
    }

    public function reactivate(
        Farmer $farmer,
        Request $request
    ): RedirectResponse {
        $reactivated = DB::transaction(function () use ($farmer, $request): bool {
            $farmer = Farmer::query()
                ->lockForUpdate()
                ->findOrFail($farmer->id);

            $previousState = (bool) $farmer->is_active;

            if ($previousState) {
                return false;
            }

            $farmer->is_active = true;
            $farmer->save();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'reactivated',
                'model' => 'Farmer',
                'model_id' => $farmer->id,
                'details' => [
                    'previous_is_active' => $previousState,
                    'new_is_active' => true,
                ],
            ]);

            return true;
        });

        if (! $reactivated) {
            return back()->with('info', 'Farmer is already active.');
        }

        return redirect()
            ->route('admin.farmers.index')
            ->with('success', 'Farmer reactivated successfully.');
    }

    public function viewValidId(Farmer $farmer)
    {
        if (! $farmer->valid_id_path) {
            abort(404, 'Valid ID not found.');
        }

        if (! Storage::disk('private')->exists($farmer->valid_id_path)) {
            abort(404, 'Valid ID file not found.');
        }

        return Storage::disk('private')->response(
            $farmer->valid_id_path
        );
    }
}
