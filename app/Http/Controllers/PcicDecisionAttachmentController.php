<?php

namespace App\Http\Controllers;

use App\Models\PcicTrackingUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcicDecisionAttachmentController extends Controller
{
    public function __invoke(Request $request, PcicTrackingUpdate $update): StreamedResponse
    {
        $user = $request->user();
        $application = $update->application;
        abort_unless($application && (
            ($user->isAdmin() && $user->hasVerifiedEmail())
            || ($user->isFarmer() && $user->farmer && $user->farmer->id === $application->farmer_id)
        ), 404);

        $disk = Storage::disk('private');
        $path = $update->attachment_path;
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, 'PCIC-decision.' . pathinfo($path, PATHINFO_EXTENSION));
    }
}
