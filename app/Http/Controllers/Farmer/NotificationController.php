<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\SmsNotification;
use App\Models\WeatherPrediction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;
        $notifications = $farmer
            ? SmsNotification::where('farmer_id', $farmer->id)->latest('sent_at')->take(10)->get()
            : collect();

        $announcements = Announcement::where('is_published', true)
            ->where('publish_date', '<=', now())
            ->latest('publish_date')
            ->take(5)
            ->get();

        $distributions = $farmer
            ? Distribution::with('program')->where('farmer_id', $farmer->id)->latest('distributed_at')->take(5)->get()
            : collect();

        $weather = WeatherPrediction::latest('fetched_at')->first();

        return view('farmer.notifications', compact('notifications', 'announcements', 'distributions', 'weather', 'farmer'));
    }
}
