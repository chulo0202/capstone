<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.farmers', [
            'farmers' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function show(Farmer $farmer): View
    {
        $farmer->load(['user', 'recommendations.program', 'distributions.program']);

        return view('admin.farmers-show', compact('farmer'));
    }

    public function destroy(Farmer $farmer): RedirectResponse
    {
        $farmer->user?->delete();

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer record deleted successfully.');
    }
}
