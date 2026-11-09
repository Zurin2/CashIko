<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\SampleDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Five-step wizard (§8.2). Step 4 collects optional extra branches. */
class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        if ($request->user()->business) {
            return redirect()->to('/home');
        }

        return Inertia::render('Onboarding', ['backgroundLocation' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|string|max:120',
            'location' => 'required|string|max:120',
            'multiBranch' => 'boolean',
            'extraBranches' => 'array|max:5',
            'extraBranches.*' => 'nullable|string|max:120',
            'withSample' => 'boolean',
        ]);

        $user = $request->user();

        $result = DB::transaction(function () use ($user, $data) {
            $business = Business::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'location' => $data['location'],
                'plan' => 'basic',
                'trial_ends_at' => now()->addDays(14)->toDateString(),
                'default_due_days' => 7,
                'default_reminder_tone' => 'gentle',
                'auto_remind' => false,
                'low_stock_threshold' => 5,
                'language' => 'taglish',
            ]);

            $branches = collect([['name' => 'Main', 'location' => $data['location']]]);
            if (! empty($data['multiBranch'])) {
                foreach (array_filter($data['extraBranches'] ?? []) as $name) {
                    $branches->push(['name' => $name, 'location' => $data['location']]);
                }
            }

            $business->branches()->createMany($branches->all());

            if (! empty($data['withSample'])) {
                app(SampleDataService::class)->load($business);
            }

            return $business;
        });

        return redirect()->intended('/home');
    }
}
