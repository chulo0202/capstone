<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\TrainingRegistration;
use App\Models\TrainingSession;
use App\Services\FarmerDecisionMailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use function Illuminate\Support\defer;

class TrainingScheduleController extends Controller
{
    public function create(): View
    {
        $farmers = $this->approvedTrainingFarmers()->orderBy('full_name')->get(['id', 'full_name', 'barangay']);

        return view('admin.training.create', compact('farmers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'farmer_ids' => ['required', 'array', 'list', 'max:10000'],
            'farmer_ids.*' => ['required', 'integer', 'distinct'],
            'title' => ['required', 'string', 'max:150'],
            'topic' => ['required', 'string', 'max:150'],
            'objectives' => ['required', 'string', 'max:3000'],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('Asia/Manila')->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'venue' => ['required', 'string', 'max:255'],
            'trainer' => ['required', 'string', 'max:255'],
            'slots' => ['required', 'integer', 'min:1', 'max:10000'],
            'instructions' => ['nullable', 'string', 'max:3000'],
        ]);

        $training = DB::transaction(function () use ($data) {
            $farmerIds = $data['farmer_ids'];
            $farmers = $this->approvedTrainingFarmers()->whereKey($farmerIds)->pluck('id');

            if ($farmers->count() !== count($farmerIds)) {
                throw ValidationException::withMessages(['farmer_ids' => 'Select farmers with approved training applications.']);
            }

            if ($farmers->count() > $data['slots']) {
                throw ValidationException::withMessages(['slots' => 'Enter enough slots for the selected farmers.']);
            }

            unset($data['farmer_ids']);
            $training = TrainingSession::create($data);
            $training->registrations()->createMany($farmers->map(fn ($id) => ['farmer_id' => $id, 'selected_by_admin' => true])->all());

            return $training;
        });

        return to_route('admin.trainings.show', $training)->with('success', 'Training draft created.');
    }

    private function approvedTrainingFarmers(): Builder
    {
        return Farmer::query()->where('is_active', true)
            ->whereHas('applications', fn (Builder $application) => $application
                ->withCurrentBenefit()
                ->where('status', 'approved')
                ->whereHas('program', fn (Builder $program) => $program
                    ->where('name', 'Training and Technology Demonstrations')));
    }

    public function show(TrainingSession $training): View
    {
        $registrations = $training->registrations()->with('farmer')->latest('id')->paginate(20);
        $confirmed = $training->registrations()->where('status', 'confirmed')->count();

        return view('admin.training.show', compact('training', 'registrations', 'confirmed'));
    }

    public function publish(TrainingSession $training): RedirectResponse
    {
        DB::transaction(function () use ($training) {
            $session = TrainingSession::lockForUpdate()->findOrFail($training->id);
            abort_unless($session->status === 'draft' && $session->isUpcoming(), 409, 'Only upcoming draft sessions can be published.');
            $session->forceFill(['status' => 'published'])->save();
        });

        return back()->with('success', 'Training published for the selected farmers.');
    }

    public function review(Request $request, TrainingSession $training, TrainingRegistration $registration): RedirectResponse
    {
        abort_unless((int) $registration->training_session_id === (int) $training->id, 404);
        $data = $request->validate([
            'status' => ['required', 'in:confirmed,rejected'],
            'reason' => ['exclude_unless:status,rejected', 'required', 'string', 'max:1000'],
        ]);

        $entry = DB::transaction(function () use ($training, $registration, $data) {
            $session = TrainingSession::lockForUpdate()->findOrFail($training->id);
            $entry = $session->registrations()->lockForUpdate()->findOrFail($registration->id);
            abort_unless($session->status === 'published' && $session->isUpcoming() && $entry->status === 'pending', 409);

            if ($data['status'] === 'confirmed' && $session->registrations()->where('status', 'confirmed')->count() >= $session->slots) {
                throw ValidationException::withMessages(['status' => 'All available slots have been confirmed.']);
            }

            $entry->forceFill($data)->save();

            return $entry;
        }, 3);

        defer(fn () => app(FarmerDecisionMailer::class)->training($entry));

        return back()->with('success', 'Training registration '.$data['status'].'.');
    }
}
