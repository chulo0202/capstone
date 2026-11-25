<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsNotification;
use App\Services\DistributionScheduleSms;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.sms', [
            'notifications' => SmsNotification::with(['announcement', 'distributionBatch', 'farmer'])
                ->when($request->filled('batch'), fn ($query) => $query->where('distribution_batch_id', $request->integer('batch')))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function retry(
        SmsNotification $notification,
        SmsService $smsService,
        DistributionScheduleSms $distributionSms
    ): RedirectResponse {
        if ($notification->distribution_batch_id) {
            $distributionSms->send($notification);

            return $notification->refresh()->status === 'submitted'
                ? back()->with('success', 'SMS submitted to Amazon SNS.')
                : back()->with('error', 'SMS submission was not confirmed. Check the current status.');
        }

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