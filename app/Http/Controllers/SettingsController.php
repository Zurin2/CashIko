<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/** Settings (§8.14): profile, utang defaults, inventory default, language + data tools. */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        // Shared props already carry business + branches; the page also needs
        // the location list which lives client-side in resources/js/lib/locations.js.
        return Inertia::render('Settings/Index');
    }

    public function update(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|string|max:120',
            'location' => 'required|string|max:120',
            'default_due_days' => 'required|integer|min:1|max:365',
            'default_reminder_tone' => 'required|in:gentle,firm',
            'auto_remind' => 'boolean',
            'low_stock_threshold' => 'required|integer|min:0|max:1000',
            'language' => 'required|in:english,taglish',
        ]);

        $business->update($data);

        return redirect()->to('/settings')->with('success', 'Saved settings.');
    }
}
