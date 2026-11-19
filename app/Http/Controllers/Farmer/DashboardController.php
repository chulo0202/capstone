<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Recommendation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;

        return view('farmer.dashboard', [
            'farmer' => $farmer,
            'announcements' => Announcement::where('is_published', true)
                ->where('publish_date', '<=', now())
                ->latest('publish_date')
                ->take(3)
                ->get(),
            'distributions' => $farmer
                ? Distribution::whereHas('benefit', fn ($benefit) => $benefit->active())
                    ->whereHas('program', fn ($program) => $program->whereNull('archived_at'))
                    ->with(['program', 'benefit'])->where('farmer_id', $farmer->id)->latest()->take(5)->get()
                : collect(),
            'applications' => $farmer
                ? Application::withCurrentBenefit()->with(['program', 'benefit'])->where('farmer_id', $farmer->id)->latest('applied_at')->take(5)->get()
                : collect(),
            'recommendations' => $farmer
                ? Recommendation::whereHas('benefit', fn ($benefit) => $benefit->active())
                    ->whereHas('program', fn ($program) => $program->whereNull('archived_at'))
                    ->with(['program', 'benefit'])->where('farmer_id', $farmer->id)->latest()->take(5)->get()
                : collect(),
        ]);
    }
}
