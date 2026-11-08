<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsNotification;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
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

    public function retry(
        SmsNotification $notification,
        SmsService $smsService
    ): RedirectResponse {
        if ($notification->status !== 'failed') {
            return back()->with(
                'error',
                'Only failed SMS notifications can be retried.'
            );
        }

        $retry = $smsService->send(
            $notification->recipient,
            $notification->message,
            $notification->announcement_id,
            $notification->farmer_id
        );

        return back()->with(
            $retry->status === 'failed' ? 'error' : 'success',
            $retry->status === 'failed'
                ? 'SMS retry failed. Please check the response details.'
                : 'SMS retry request processed. Current status: '
                    . ucfirst($retry->status) . '.'
        );
    }
}