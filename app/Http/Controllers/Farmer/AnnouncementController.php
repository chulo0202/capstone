<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('farmer.announcements', [
            'announcements' => Announcement::where('is_published', true)
                ->where('publish_date', '<=', now())
                ->latest('publish_date')
                ->paginate(10),
        ]);
    }
}
