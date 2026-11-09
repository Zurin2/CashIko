<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

/** Date-range and branch-scoping helpers shared by every controller (spéc §7). */
class Period
{
    public static function today(): Carbon
    {
        return now()->startOfDay();
    }

    /** Monday-based week start, matching the spec's startOfWeekISO. */
    public static function week(): Carbon
    {
        return now()->startOfWeek();
    }

    public static function month(): Carbon
    {
        return now()->startOfMonth();
    }

    /** Resolve a range chip: today | week | month | all → start Carbon or null. */
    public static function resolve(?string $range): ?Carbon
    {
        return match ($range) {
            'today' => self::today(),
            'week' => self::week(),
            'month' => self::month(),
            default => null,
        };
    }

    public static function daysBack(int $n): Carbon
    {
        return now()->subDays($n - 1)->startOfDay();
    }

    /** Local YYYY-MM-DD key used to group daily summaries. */
    public static function dayKey(CarbonImmutable|Carbon|string $date): string
    {
        return Carbon::parse($date)->timezone(config('app.timezone'))->format('Y-m-d');
    }

    /**
     * Branch scoping (§7.12): ?branch=all|<id>. Invalid ids fall back to "all".
     * Returns [branchId|null(all), appliedId|null for writes fallback].
     */
    public static function branch($business, ?string $param): array
    {
        $selected = null; // null = all branches
        $fallback = null; // write attribution fallback (first branch)

        if ($param && $param !== 'all') {
            $branch = $business->branches()->find($param);
            $selected = $branch?->id;
        }

        $first = $business->branches()->first();
        $fallback = $first?->id;

        return [$selected, $fallback];
    }
}
