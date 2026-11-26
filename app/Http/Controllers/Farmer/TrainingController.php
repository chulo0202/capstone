<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\TrainingRegistration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $farmerId = $request->user()->farmer?->id;
        $statuses = ['pending', 'confirmed', 'rejected'];
        $status = $request->query('status');
        $status = in_array($status, $statuses, true) ? $status : null;

        $registrations = TrainingRegistration::query()
            ->where('farmer_id', $farmerId)
            ->where('selected_by_admin', true)
            ->when($status, fn ($query) => $query->where('training_registrations.status', $status))
            ->join('training_sessions', 'training_sessions.id', '=', 'training_registrations.training_session_id')
            ->where('training_sessions.status', 'published')
            ->orderByDesc('training_sessions.scheduled_date')
            ->orderByDesc('training_sessions.start_time')
            ->orderByDesc('training_registrations.id')
            ->select('training_registrations.*')
            ->with('trainingSession')
            ->paginate(12)
            ->appends($status ? ['status' => $status] : []);

        return view('farmer.trainings', compact('registrations', 'statuses', 'status'));
    }

    public function show(Request $request, TrainingRegistration $registration): View
    {
        abort_unless((int) $registration->farmer_id === (int) $request->user()->farmer?->id, 404);

        $training = $registration->trainingSession;
        abort_unless($training->status === 'published', 404);

        return view('farmer.training-registration', [
            'training' => $training,
            'registration' => $registration,
        ]);
    }
}
