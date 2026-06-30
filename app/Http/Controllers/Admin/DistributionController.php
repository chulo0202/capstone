<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(): View
    {
        return view('admin.distributions', [
            'distributions' => Distribution::with(['farmer', 'program', 'releasedBy'])
                ->latest('distributed_at')
                ->paginate(10),
            'programs' => Program::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function scan(): View
    {
        return view('admin.distributions-scan');
    }

    public function verify(Request $request, EligibilityService $eligibilityService): View|RedirectResponse
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        $data = json_decode($request->qr_data, true);

        if (! $data || ! isset($data['farmer_id'], $data['token'])) {
            return back()->with('error', 'Invalid QR code data.');
        }

        $farmer = Farmer::with('user')->find($data['farmer_id']);

        if (! $farmer || $farmer->qr_code_token !== $data['token']) {
            return back()->with('error', 'Farmer verification failed. QR code is invalid or expired.');
        }

        $programs = Program::where('is_active', true)->get()->map(function ($program) use ($farmer, $eligibilityService) {
            $evaluation = $eligibilityService->evaluate($farmer, $program);
            $alreadyReleased = Distribution::where('farmer_id', $farmer->id)
                ->where('program_id', $program->id)
                ->exists();

            return [
                'program' => $program,
                'status' => $evaluation['status'],
                'missing' => $evaluation['missing_requirements'],
                'already_released' => $alreadyReleased,
            ];
        });

        return view('admin.distributions-verify', compact('farmer', 'programs'));
    }

    public function release(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'program_id' => 'required|exists:programs,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $exists = Distribution::where('farmer_id', $validated['farmer_id'])
            ->where('program_id', $validated['program_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Benefit already released for this farmer and program.');
        }

        Distribution::create([
            'farmer_id' => $validated['farmer_id'],
            'program_id' => $validated['program_id'],
            'released_by' => $request->user()->id,
            'distributed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('admin.distributions.index')
            ->with('success', 'Benefit released successfully.');
    }
}
