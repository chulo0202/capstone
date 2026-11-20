<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Services\FarmerNotifications;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, FarmerNotifications $notifications): View
    {
        $farmer = $request->user()->farmer;

        return view('farmer.notifications', [
            'farmer' => $farmer,
            ...$notifications->feed($farmer, $request->isMethod('GET')),
        ]);
    }
}
