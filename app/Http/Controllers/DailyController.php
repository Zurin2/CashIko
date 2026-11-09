<?php

namespace App\Http\Controllers;

use App\Support\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Daily Summary (§8.10): today hero, 14-day chart, expandable day list. */
class DailyController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        $sales = $business->sales()->active()
            ->where('date_time', '>=', Period::daysBack(30))
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->orderBy('date_time')
            ->get();

        $expenses = $business->expenses()
            ->where('date', '>=', Period::daysBack(30)->toDateString())
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $utangEntries = $business->utangEntries()
            ->where('date', '>=', Period::daysBack(30)->toDateString())
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $salesByDay = $sales->groupBy(fn ($s) => Period::dayKey($s->date_time));
        $expensesByDay = $expenses->groupBy(fn ($e) => $e->date->format('Y-m-d'));
        $utangByDay = $utangEntries->groupBy(fn ($e) => $e->date->format('Y-m-d'));

        $days = collect();
        foreach (range(0, 29) as $i) {
            $key = Period::dayKey(Period::today()->subDays($i));
            $daySales = $salesByDay->get($key, collect());
            $dayExpenses = $expensesByDay->get($key, collect());
            $dayUtang = $utangByDay->get($key, collect());

            if ($daySales->isEmpty() && $dayExpenses->isEmpty() && $dayUtang->isEmpty()) {
                continue;
            }

            $items = $daySales->flatMap(fn ($s) => $s->items)->groupBy(fn ($l) => $l['product_id'] ?? $l['name']);
            $topItems = $items->map(fn ($g) => [
                'name' => $g->first()['name'],
                'qty' => array_sum(array_map(fn ($i) => $i['quantity'], $g->all())),
            ])->sortByDesc('qty')->take(3)->values();

            $today = $key === Period::dayKey(now());

            $days->push([
                'key' => $key,
                'label' => match (true) {
                    $today => 'Today',
                    $key === Period::dayKey(now()->subDay()) => 'Yesterday',
                    default => Period::today()->subDays($i)->format('D, M j'),
                },
                'sales' => round($daySales->sum('total'), 2),
                'cost' => round($daySales->sum('total_cost'), 2),
                'txns' => $daySales->count(),
                'expenses' => round($dayExpenses->sum('amount'), 2),
                'profit' => round($daySales->sum('total') - $daySales->sum('total_cost') - $dayExpenses->sum('amount'), 2),
                'payments' => round($dayUtang->where('type', 'payment')->sum('amount'), 2),
                'newUtang' => round($dayUtang->where('type', 'utang')->sum('amount'), 2),
                'paymentBreakdown' => $daySales->groupBy('payment_type')->map(fn ($g) => round($g->sum('total'), 2))->all(),
                'topItems' => $topItems->all(),
            ]);
        }

        $today = $days->firstWhere('key', Period::dayKey(now()));

        return Inertia::render('Daily/Index', [
            'today' => $today ?: ['sales' => 0, 'profit' => 0, 'txns' => 0, 'expenses' => 0],
            'chart' => collect()
                ->range(0, 13)
                ->map(function ($i) use ($sales) {
                    $day = Period::today()->subDays(13 - $i);
                    $key = Period::dayKey($day);
                    $daySales = $sales->filter(fn ($s) => Period::dayKey($s->date_time) === $key);

                    return [
                        'date' => $key,
                        'label' => $day->format('M d'),
                        'sales' => round($daySales->sum('total'), 2),
                    ];
                })
                ->values(),
            'days' => $days->reverse()->values(),
        ]);
    }
}
