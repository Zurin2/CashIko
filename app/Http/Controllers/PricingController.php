<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/** Pricing (§8.13): mocked plans — plan toggle writes Business.plan directly. */
class PricingController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;

        $trialDays = 0;
        if ($business->trial_ends_at) {
            $trialDays = max(0, now()->startOfDay()->diffInDays($business->trial_ends_at, false));
        }

        return Inertia::render('Pricing/Index', [
            'trialDays' => $trialDays,
        ]);
    }

    public function setPlan(Request $request)
    {
        $data = $request->validate(['plan' => 'required|in:basic,premium']);

        $request->user()->business->update(['plan' => $data['plan']]);

        return redirect()->to('/pricing')->with('success', 'Mocked payment for prototype — no real charge. Plan is set to '.ucfirst($data['plan']).'.');
    }
}
