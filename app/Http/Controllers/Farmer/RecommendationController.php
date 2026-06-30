<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;

        $recommendations = $farmer
            ? $farmer->recommendations()->with('program')->latest()->get()
            : collect();

        return view('farmer.recommendations', compact('recommendations', 'farmer'));
    }
}
