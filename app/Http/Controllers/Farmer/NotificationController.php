<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Services\FarmerNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, FarmerNotifications $notifications): View
    {
        $farmer = $request->user()->farmer;

        $browseAnnouncements = $request->query('category') === 'announcement';

        return view('farmer.notifications', [
            'farmer' => $farmer,
            'browseAnnouncements' => $browseAnnouncements,
            ...$notifications->feed($farmer, $request->isMethod('GET'), $browseAnnouncements),
        ]);
    }

    public function distributionSchedule(Request $request, int $assignment, FarmerNotifications $notifications): View|RedirectResponse
    {
        $schedule = $notifications->distributionSchedules($request->user()->farmer)->findOrFail($assignment);

        if ($schedule->released_at) {
            $receipt = Distribution::where('farmer_id', $request->user()->farmer->id)
                ->where('distribution_batch_id', $schedule->distribution_batch_id)
                ->where('application_id', $schedule->application_id)
                ->firstOrFail();

            return to_route('farmer.notifications.distribution-receipt', $receipt);
        }

        return view('farmer.distribution-details', [
            'batch' => $schedule->batch,
            'applicationId' => $schedule->application_id,
            'distribution' => null,
        ]);
    }

    public function distributionReceipt(Request $request, int $distribution): View
    {
        $receipt = Distribution::with('program', 'benefit', 'batch.program', 'batch.benefit')
            ->where('farmer_id', $request->user()->farmer->id)
            ->findOrFail($distribution);

        return view('farmer.distribution-details', [
            'batch' => $receipt->batch,
            'applicationId' => $receipt->application_id,
            'distribution' => $receipt,
        ]);
    }
}
