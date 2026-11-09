<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Shared props mirror the spec's AuthContext + BusinessContext:
     * every screen knows who is signed in, their business, and its branches.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $business = $user?->business;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_superadmin' => $user->is_superadmin,
                ] : null,
            ],
            'business' => $business ? $this->businessProps($business) : null,
            'branches' => $business ? $business->branches()->get()->map(
                fn ($b) => ['id' => $b->id, 'name' => $b->name, 'location' => $b->location, 'is_active' => $b->is_active]
            )->all() : [],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    private function businessProps($b): array
    {
        return [
            'id' => $b->id,
            'name' => $b->name,
            'type' => $b->type,
            'location' => $b->location,
            'plan' => $b->plan,
            'trial_ends_at' => $b->trial_ends_at?->toDateString(),
            'default_due_days' => $b->default_due_days,
            'default_reminder_tone' => $b->default_reminder_tone,
            'auto_remind' => $b->auto_remind,
            'low_stock_threshold' => $b->low_stock_threshold,
            'language' => $b->language,
        ];
    }
}
