<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\PcicTrackingUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcicDecisionAttachmentController extends Controller
{
    public function __invoke(Request $request, PcicTrackingUpdate $update): StreamedResponse
    {
        return $this->download($request, $update->application, $update->attachment_path, 'PCIC-decision');
    }

    public function loan(Request $request, Application $application): StreamedResponse
    {
        return $this->download($request, $application, $application->loan_decision_attachment_path, 'Loan-decision');
    }

    private function download(Request $request, ?Application $application, ?string $path, string $filename): StreamedResponse
    {
        $user = $request->user();
        abort_unless($application && (
            ($user->isAdmin() && $user->hasVerifiedEmail())
            || ($user->isFarmer() && $user->farmer && $user->farmer->id === $application->farmer_id)
        ), 404);

        $disk = Storage::disk('private');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, $filename . '.' . pathinfo($path, PATHINFO_EXTENSION));
    }
}
