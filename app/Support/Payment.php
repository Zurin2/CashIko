<?php

namespace App\Support;

/** Payment method labels + colour classes (§7.1 paymentLabel/paymentColor). */
class Payment
{
    public static function label(string $p): string
    {
        return match ($p) {
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'maya' => 'Maya',
            'utang' => 'Utang',
            default => ucfirst($p),
        };
    }

    public static function color(string $p): string
    {
        return match ($p) {
            'cash' => 'text-emerald-600',
            'gcash' => 'text-sky-600',
            'maya' => 'text-violet-600',
            'utang' => 'text-orange-600',
            default => 'text-stone-600',
        };
    }
}
