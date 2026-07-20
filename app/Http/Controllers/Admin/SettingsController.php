<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($request->filled('password')) {
            $request->validate(['password' => 'confirmed|min:8']);
            $user->password = bcrypt($request->input('password'));
            $user->save();
        }

        return back()->with('success', 'Settings updated.');
    }
}
