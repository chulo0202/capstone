<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsNotification;
use Illuminate\View\View;

class SmsNotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.sms', [
            'notifications' => SmsNotification::with(['announcement', 'farmer'])
                ->latest()
                ->paginate(15),
        ]);
    }
}
