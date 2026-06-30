<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Distribution;
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
        ]);
    }
}
