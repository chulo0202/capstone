<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements', [
            'announcements' => Announcement::with('creator')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, SmsService $smsService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required|date',
            'sms_enabled' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $validated['sms_enabled'] = $request->boolean('sms_enabled');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['created_by'] = $request->user()->id;

        $announcement = Announcement::create($validated);

        if ($announcement->is_published && $announcement->sms_enabled) {
            $smsService->sendAnnouncementSms(
                $announcement->id,
                $announcement->title,
                $announcement->content
            );
        }

        return back()->with('success', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement, SmsService $smsService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required|date',
            'sms_enabled' => 'boolean',
            'is_published' => 'boolean',
        ]);

        $wasPublished = $announcement->is_published;

        $validated['sms_enabled'] = $request->boolean('sms_enabled');
        $validated['is_published'] = $request->boolean('is_published');

        $announcement->update($validated);

        if (! $wasPublished && $announcement->is_published && $announcement->sms_enabled) {
            $smsService->sendAnnouncementSms(
                $announcement->id,
                $announcement->title,
                $announcement->content
            );
        }

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    }
}
