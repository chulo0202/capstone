<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;

        $applications = $farmer
            ? Application::with('program')->where('farmer_id', $farmer->id)->latest('applied_at')->get()
            : collect();

        return view('farmer.applications', compact('applications', 'farmer'));
    }

    public function store(Program $program): RedirectResponse
    {
        $farmer = Auth::user()->farmer;

        if (! $farmer || ! $farmer->profile_completed) {
            return back()->with('error', 'Please complete your profile before applying.');
        }

        $exists = Application::where('farmer_id', $farmer->id)
            ->where('program_id', $program->id)
            ->exists();

        if ($exists) {
            return back()->with('info', 'You already applied for this program.');
        }

        Application::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'status' => 'pending',
            'remarks' => 'Submitted through farmer portal',
            'applied_at' => now(),
        ]);

        return back()->with('success', 'Application submitted successfully.');
    }
}
