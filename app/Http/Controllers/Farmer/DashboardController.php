<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Recommendation;
use App\Models\WeatherPrediction;
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
                ? Distribution::with('program')->where('farmer_id', $farmer->id)->latest()->take(5)->get()
                : collect(),
            'applications' => $farmer
                ? Application::with('program')->where('farmer_id', $farmer->id)->latest('applied_at')->take(5)->get()
                : collect(),
            'recommendations' => $farmer
                ? Recommendation::with('program')->where('farmer_id', $farmer->id)->latest()->take(5)->get()
                : collect(),
            'weather' => WeatherPrediction::latest('fetched_at')->first(),
        ]);
    }
}
