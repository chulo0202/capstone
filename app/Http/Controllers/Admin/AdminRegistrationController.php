<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRegistrationController extends Controller
{
    /**
     * Display admin registration console.
     */
    public function index(): View
    {
        $pendingAdmins = User::where('role', 'admin')
            ->where('admin_registration_status', 'pending')
            ->latest()
            ->get();

        $totalAdmins = User::where('role', 'admin')->count();

        $approvedAdmins = User::where('role', 'admin')
            ->where('admin_registration_status', 'approved')
            ->count();

        $pendingCount = User::where('role', 'admin')
            ->where('admin_registration_status', 'pending')
            ->count();

        $declinedAdmins = User::where('role', 'admin')
            ->where('admin_registration_status', 'declined')
            ->count();

        return view('admin.admin-registrations.index', compact(
            'pendingAdmins',
            'totalAdmins',
            'approvedAdmins',
            'pendingCount',
            'declinedAdmins'
        ));
    }

    /**
     * Approve an admin registration.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            abort(403, 'You cannot change your own admin status.');
        }

        if (
            $user->role !== 'admin'
            || $user->admin_registration_status !== 'pending'
            || $user->email_verified_at === null
        ) {
            abort(404);
        }

        $user->update([
            'admin_registration_status' => 'approved',
        ]);

        return redirect()
            ->route('admin.admin-registrations.index')
            ->with('success', 'Admin account approved successfully.');
    }

    /**
     * Decline an admin registration.
     */
    public function decline(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            abort(403, 'You cannot change your own admin status.');
        }

        if (
            $user->role !== 'admin'
            || $user->admin_registration_status !== 'pending'
            || $user->email_verified_at === null
        ) {
            abort(404);
        }

        $user->update([
            'admin_registration_status' => 'declined',
        ]);

        return redirect()
            ->route('admin.admin-registrations.index')
            ->with('success', 'Admin account declined successfully.');
    }
}