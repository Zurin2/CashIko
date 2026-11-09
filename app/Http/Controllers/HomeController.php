<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use App\Support\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Home dashboard (§8.3): today stats, quick actions, 7-day chart, low stock, recent activity. */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        $todaySales = $business->sales()->active()
            ->where('date_time', '>=', Period::today())
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $weekSales = $business->sales()->active()
            ->where('date_time', '>=', Period::daysBack(7))
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $products = $business->products()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $utangEntries = $business->utangEntries()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $todayExpenses = $business->expenses()
            ->where('date', '>=', Period::today()->toDateString())
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $todayProfit = SalesAnalyticsService::profit($todaySales);
        $weekProfit = SalesAnalyticsService::profit($weekSales);

        $utangOut = round($utangEntries->where('type', 'utang')->sum('amount')
            - $utangEntries->where('type', 'payment')->sum('amount'), 2);

        $lowStock = $products->filter(fn ($p) => $p->quantity <= $p->low_stock_threshold)
            ->values();

        $owing = $utangEntries->groupBy('customer_id')
            ->filter(function ($g) {
                $b = $g->where('type', 'utang')->sum('amount') - $g->where('type', 'payment')->sum('amount');

                return $b > 0.009;
            })->count();

        $recent = $business->sales()->active()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->orderByDesc('date_time')
            ->limit(5)
            ->get();

        return Inertia::render('Home/Index', [
            'stats' => [
                'todaySales' => $todayProfit['total'],
                'todayTxns' => $todayProfit['count'],
                'todayProfit' => $todayProfit['profit'],
                'utangOut' => max(0, $utangOut),
                'utangPeople' => $owing,
                'lowStockCount' => $lowStock->count(),
                'weekSales' => $weekProfit['total'],
                'weekProfit' => $weekProfit['profit'],
                'todayExpenses' => round($todayExpenses->sum('amount'), 2),
            ],
            'chart' => SalesAnalyticsService::series($weekSales, 7),
            'lowStock' => $lowStock->take(4)->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'unit' => $p->unit,
                'threshold' => $p->low_stock_threshold,
                'out' => $p->quantity <= 0,
            ]),
            'recent' => $recent->map(fn ($s) => [
                'id' => $s->id,
                'summary' => collect($s->items)->map(fn ($i) => sprintf('%g× %s', $i['quantity'], $i['name']))->implode(', '),
                'date_time' => $s->date_time->toISOString(),
                'payment_type' => $s->payment_type,
                'total' => $s->total,
            ]),
        ]);
    }
}
