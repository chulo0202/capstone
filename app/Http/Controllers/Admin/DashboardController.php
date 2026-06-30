<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\WeatherService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(WeatherService $weatherService): View
    {
        $weather = $weatherService->getLatest() ?? $weatherService->fetchAndStore();

        return view('admin.dashboard', [
            'totalFarmers' => Farmer::count(),
            'totalPrograms' => Program::where('is_active', true)->count(),
            'totalBeneficiaries' => Distribution::distinct('farmer_id')->count('farmer_id'),
            'recentDistributions' => Distribution::with(['farmer', 'program'])->latest()->take(5)->get(),
            'recentAnnouncements' => Announcement::latest()->take(3)->get(),
            'weather' => $weather,
        ]);
    }
}
