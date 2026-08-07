<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeatherPrediction;
use App\Services\AtRiskFarmerService;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeatherController extends Controller
{
    public function index(
        WeatherService $weatherService,
        AtRiskFarmerService $riskService
    ): View
    {
        return view('admin.weather', [
            'current' => $weatherService->getLatest(),

            'history' => WeatherPrediction::latest('fetched_at')
                ->take(10)
                ->get(),

            'atRiskFarmers' => $riskService->getAtRiskFarmers(),
        ]);
    }

    public function refresh(
        WeatherService $weatherService
    ): RedirectResponse
    {
        $weatherService->fetchAndStore();

        return redirect()
            ->route('admin.weather.index')
            ->with('success', 'Weather data refreshed successfully.');
    }
}