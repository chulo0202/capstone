<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeatherPrediction;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WeatherController extends Controller
{
    public function index(WeatherService $weatherService): View
    {
        $current = $weatherService->getLatest();
        $history = WeatherPrediction::latest('fetched_at')->take(10)->get();

        return view('admin.weather', compact('current', 'history'));
    }

    public function refresh(WeatherService $weatherService): RedirectResponse
    {
        $weatherService->fetchAndStore();

        return back()->with('success', 'Weather data refreshed successfully.');
    }
}
