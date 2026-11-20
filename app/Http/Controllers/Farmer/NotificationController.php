<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Distribution;
use App\Models\SmsNotification;
use App\Models\PcicTrackingUpdate;
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
            ? Distribution::with(['program', 'benefit'])->where('farmer_id', $farmer->id)->latest('distributed_at')->take(5)->get()
            : collect();

        $pcicUpdates = $farmer
            ? PcicTrackingUpdate::whereHas('application', fn ($query) => $query->where('farmer_id', $farmer->id))
                ->with('application.benefit')->latest('id')->take(10)->get()
            : collect();

        return view('farmer.notifications', compact('notifications', 'announcements', 'distributions', 'farmer', 'pcicUpdates'));
    }
}
