<?php

namespace App\Services;

use App\Support\Period;
use Carbon\Carbon;

/**
 * Read-model aggregations shared by Home, Reports and Daily Summary.
 * Single profit formula (§7.2): profit = sales − cost − expenses,
 * only status=active sales.
 */
class SalesAnalyticsService
{
    public static function sums($sales): array
    {
        return [
            'total' => round($sales->sum('total'), 2),
            'cost' => round($sales->sum('total_cost'), 2),
            'count' => $sales->count(),
        ];
    }

    public static function profit($sales): array
    {
        $sums = self::sums($sales);

        return $sums + ['profit' => round($sums['total'] - $sums['cost'], 2)];
    }

    public static function byDay($sales, ?Carbon $from = null): array
    {
        $groups = $sales->groupBy(fn ($s) => Period::dayKey($s->date_time))
            ->map(fn ($day) => ['sales' => $day->sum('total'), 'txn_count' => $day->count()]);

        return $groups->all();
    }

    public static function series($sales, int $days): array
    {
        $byDay = self::byDay($sales);
        $out = [];
        foreach (range(0, $days - 1) as $i) {
            $date = Period::today()->subDays($days - 1 - $i);
            $key = $date->format('Y-m-d');
            $out[] = [
                'date' => $key,
                'label' => $date->format('M d'),
                'sales' => round($byDay[$key]['sales'] ?? 0, 2),
            ];
        }

        return $out;
    }

    /** Best sellers: revenue + qty from denormalized sale lines. */
    public static function topProducts($sales, int $limit = 5): array
    {
        $sku = [];
        foreach ($sales as $sale) {
            foreach ($sale->items ?? [] as $line) {
                $key = $line['product_id'] ?? $line['name'];
                $sku[$key]['name'] = $line['name'];
                $sku[$key]['qty'] = ($sku[$key]['qty'] ?? 0) + $line['quantity'];
                $sku[$key]['rev'] = ($sku[$key]['rev'] ?? 0) + $line['quantity'] * $line['unit_price'];
            }
        }

        return collect($sku)->sortByDesc('rev')->take($limit)->values()->all();
    }

    public static function byPayment($sales): array
    {
        return $sales->groupBy('payment_type')->map(fn ($g) => round($g->sum('total'), 2))->all();
    }

    public static function expensesByCategory($expenses): array
    {
        return $expenses->groupBy(fn ($e) => $e->category === 'Other' && $e->custom_category
            ? $e->custom_category
            : $e->category)
            ->map(fn ($g) => round($g->sum('amount'), 2))
            ->toArray();
    }
}
