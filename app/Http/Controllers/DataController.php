<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Data tools wired to Settings (spec §14 resetData). */
class DataController extends Controller
{
    public function reset(Request $request)
    {
        $business = $request->user()->business;

        // Delete every record of all 9 tenant entities + the Business itself;
        // per-entity failures are ignored (spec §14).
        collect([
            $business->chatMessages(), $business->reminders(), $business->stockMovements(),
            $business->expenses(), $business->utangEntries(), $business->sales(),
            $business->customers(), $business->products(), $business->branches(),
        ])->each(fn ($query) => $query->get()->each(fn ($model) => $model->delete()));

        $business->delete();

        return redirect()->to('/')->with('success', __('All data has been reset.'));
    }
}
