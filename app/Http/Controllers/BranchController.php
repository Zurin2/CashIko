<?php

namespace App\Http\Controllers;

use App\Support\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Branches (§8.11): premium-only management + today leaderboard. */
class BranchController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;

        $branches = $business->branches()->orderBy('created_at')->get();
        $today = Period::today();

        $stats = $branches->mapWithKeys(function ($b) use ($business, $today) {
            $sales = $business->sales()->active()->whereBranchId($b->id)->where('date_time', '>=', $today)->get();
            $products = $business->products()->whereBranchId($b->id)->get();
            $lowCount = $products->filter(fn ($p) => $p->quantity <= max($p->low_stock_threshold, $business->low_stock_threshold))->count();
            $cost = $sales->sum('total_cost');

            return [$b->id => [
                'sales' => round($sales->sum('total'), 2),
                'txns' => $sales->count(),
                'profit' => round($sales->sum('total') - $cost, 2),
                'low' => $lowCount,
            ]];
        });

        return Inertia::render('Branches/Index', [
            'branches' => $branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'location' => $b->location,
                'house_number' => $b->house_number,
                'street' => $b->street,
                'barangay' => $b->barangay,
                'city' => $b->city,
                'is_active' => $b->is_active,
                'stats' => $stats[$b->id],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;
        if ($business->plan !== 'premium') {
            return redirect()->to('/branches')->with('error', 'Premium feature.');
        }

        $data = $this->validated($request);
        $business->branches()->create($data + ['location' => $this->composeLocation($data)]);

        return redirect()->to('/branches')->with('success', 'Branch added — restored in every dropdown.');
    }

    public function update(Request $request, $branchId)
    {
        $business = $request->user()->business;
        $branch = $business->branches()->findOrFail($branchId);

        $data = $this->validated($request, $branch->name === 'Main');
        $branch->update($data + ['location' => $this->composeLocation($data)]);

        return redirect()->to('/branches')->with('success', "Na-update ang {$branch->name}.");
    }

    public function destroy(Request $request, $branchId)
    {
        $business = $request->user()->business;
        $branch = $business->branches()->findOrFail($branchId);

        $mainName = $business->branches()->orderBy('created_at')->first()->name;
        if ($branch->name === $mainName) {
            return redirect()->to('/branches')->with('error', "Cannot delete $mainName branch.");
        }

        // Deactivation never touches historical records; delete is soft via is_active.
        $branch->update(['is_active' => false]);

        return redirect()->to('/branches')->with('success', "Na-deactivate ang {$branch->name}.");
    }

    private function validated(Request $request, bool $main = false): array
    {
        $rules = [
            'house_number' => 'nullable|string|max:40',
            'street' => 'nullable|string|max:120',
            'barangay' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'is_active' => 'boolean',
        ];

        if (! $main) {
            $rules['name'] = 'required|string|max:120';
        }

        return $request->validate($rules) + ['is_active' => (bool) $request->boolean('is_active', true)];
    }

    private function composeLocation(array $data): ?string
    {
        return collect([$data['house_number'] ?? null, $data['street'] ?? null, $data['barangay'] ?? null, $data['city'] ?? null])
            ->filter()->implode(', ') ?: null;
    }
}
