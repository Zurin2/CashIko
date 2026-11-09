<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalyticsService;
use App\Support\Payment;
use App\Support\Period;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Reports (§8.9) + CSV export. Range chips: today / week / month. */
class ReportsController extends Controller
{
    private function window(Request $request): array
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));
        $range = $request->query('range', 'week');
        $start = Period::resolve($range) ?: Period::week();

        $sales = $business->sales()->active()
            ->where('date_time', '>=', $start)
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->orderBy('date_time')
            ->get();

        $expenses = $business->expenses()
            ->where('date', '>=', $start->toDateString())
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->get();

        $utangEntries = $business->utangEntries()
            ->get(); // §7.12: utang aggregation stays business-wide in v1

        return [$business, $branchId, $range, $start, $sales, $expenses, $utangEntries];
    }

    public function index(Request $request)
    {
        [$business, $branchId, $range, $start, $sales, $expenses, $utangEntries] = $this->window($request);

        $profit = SalesAnalyticsService::profit($sales);
        $deadCutoff = now()->subDays(30);
        $products = $business->products()->get();

        $deadStock = $products->filter(
            fn ($p) => $p->quantity > 0 && ($p->last_sold_at === null || $p->last_sold_at->lt($deadCutoff))
        )->values();

        return Inertia::render('Reports/Index', [
            'range' => $range,
            'profit' => [
                'sales' => $profit['total'],
                'cost' => $profit['cost'],
                'expenses' => round($expenses->sum('amount'), 2),
                'realProfit' => round($profit['profit'] - $expenses->sum('amount'), 2),
                'margin' => $profit['total'] > 0 ? round(($profit['profit'] - $expenses->sum('amount')) / $profit['total'] * 100, 1) : 0,
            ],
            'trend' => SalesAnalyticsService::series($sales, 7),
            'topProducts' => SalesAnalyticsService::topProducts($sales, 5),
            'payments' => SalesAnalyticsService::byPayment($sales),
            'expensesByCategory' => SalesAnalyticsService::expensesByCategory($expenses),
            'utang' => [
                'outstanding' => round(
                    $utangEntries->where('type', 'utang')->sum('amount')
                    - $utangEntries->where('type', 'payment')->sum('amount'), 2
                ),
                'inWindow' => round($utangEntries->where('type', 'payment')->filter(
                    fn ($e) => $e->date->gt($start)
                )->sum('amount'), 2),
            ],
            'deadStock' => $deadStock->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'quantity' => $p->quantity,
                'unit' => $p->unit,
                'value' => round($p->quantity * $p->cost_price, 2),
            ]),
        ]);
    }

    /** cashiko-sales.csv: Date, Items, Payment, Total, Cost — active window only. */
    public function csv(Request $request): StreamedResponse
    {
        [, , , , $sales] = $this->window($request);

        $filename = 'cashiko-sales-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Items', 'Payment', 'Total', 'Cost']);
            foreach ($sales as $sale) {
                fputcsv($out, [
                    $sale->date_time->format('Y-m-d H:i'),
                    collect($sale->items)->map(fn ($i) => sprintf('%g× %s', $i['quantity'], $i['name']))->implode('; '),
                    Payment::label($sale->payment_type),
                    number_format($sale->total, 2),
                    number_format($sale->total_cost, 2),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
