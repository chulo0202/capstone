<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\TrainingRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $farmer = $request->user()->farmer;
        $trainings = TrainingSession::where('status', 'published')
            ->whereDate('scheduled_date', '>=', today('Asia/Manila'))
            ->with(['registrations' => fn ($query) => $query->where('farmer_id', $farmer?->id)])
            ->withCount(['registrations as confirmed_count' => fn ($query) => $query->where('status', 'confirmed')])
            ->orderBy('scheduled_date')->orderBy('start_time')->paginate(12);

        return view('farmer.trainings', compact('trainings', 'farmer'));
    }

    public function show(Request $request, TrainingRegistration $registration): View
    {
        abort_unless((int) $registration->farmer_id === (int) $request->user()->farmer?->id, 404);

        return view('farmer.training-registration', [
            'training' => $registration->trainingSession,
            'registration' => $registration,
        ]);
    }

    public function store(Request $request, TrainingSession $training): RedirectResponse
    {
        $farmer = $request->user()->farmer;
        abort_unless($farmer?->is_active && $farmer->profile_completed, 403);

        $created = DB::transaction(function () use ($training, $farmer) {
            $session = TrainingSession::lockForUpdate()->findOrFail($training->id);
            abort_unless($session->status === 'published' && $session->isUpcoming(), 404);
            $existing = $session->registrations()->where('farmer_id', $farmer->id)->first();
            if ($existing) {
                return false;
            }

            abort_if($session->registrations()->where('status', 'confirmed')->count() >= $session->slots, 409, 'All training slots are filled.');
            $session->registrations()->create(['farmer_id' => $farmer->id]);

            return true;
        }, 3);

        return back()->with('success', $created ? 'Registration submitted for review.' : 'You already registered for this training.');
    }
}
