<?php

namespace App\Services;

use App\Models\Business;
use App\Support\Period;
use Illuminate\Support\Facades\Http;

/**
 * "Tanong kay CashIko" (§11.1). v1 has an optional external LLM
 * (ASSISTANT_URL + ASSISTANT_KEY, OpenAI-compatible /chat/completions);
 * without it a deterministic Taglish summarizer answers from the same
 * context snapshot, so the feature always works.
 */
class AssistantService
{
    public function buildContext(Business $business): array
    {
        $analytics = new SalesAnalyticsService;

        $todaySales = $business->sales()->active()->where('date_time', '>=', Period::today())->get();
        $weekSales = $business->sales()->active()->where('date_time', '>=', Period::week())->get();
        $monthSales = $business->sales()->active()->where('date_time', '>=', Period::month())->get();
        $monthExpenses = $business->expenses()->where('date', '>=', Period::month()->toDateString())->get();

        $entries = $business->utangEntries()->get();
        $owers = $entries->groupBy('customer_name')->map(function ($g) {
            return round($g->where('type', 'utang')->sum('amount') - $g->where('type', 'payment')->sum('amount'), 2);
        })->filter(fn ($b) => $b > 0.009)->sortDesc();

        $deadCutoff = now()->subDays(30);
        $products = $business->products()->get();

        return [
            'business' => [
                'name' => $business->name,
                'type' => $business->type,
                'location' => $business->location ?: '',
            ],
            'today' => [
                'sales' => round($todaySales->sum('total'), 2),
                'txns' => $todaySales->count(),
                'profit' => round(
                    $todaySales->sum('total') - $todaySales->sum('total_cost')
                    - $business->expenses()->where('date', '>=', Period::today()->toDateString())->sum('amount'), 2
                ),
            ],
            'week' => [
                'sales' => round($weekSales->sum('total'), 2),
                'profit' => round(
                    $weekSales->sum('total') - $weekSales->sum('total_cost')
                    - $business->expenses()->where('date', '>=', Period::week()->toDateString())->sum('amount'), 2
                ),
            ],
            'month' => [
                'sales' => round($monthSales->sum('total'), 2),
                'expenses' => round($monthExpenses->sum('amount'), 2),
                'profit' => round(
                    $monthSales->sum('total') - $monthSales->sum('total_cost') - $monthExpenses->sum('amount'), 2
                ),
            ],
            'topProducts' => collect($analytics->topProducts($monthSales, 5))->values()->all(),
            'lowStock' => $products->filter(fn ($p) => $p->quantity <= $p->low_stock_threshold)
                ->values()->map(fn ($p) => ['name' => $p->name, 'qty' => $p->quantity, 'unit' => $p->unit])->all(),
            'utang' => [
                'totalOutstanding' => round(
                    $entries->where('type', 'utang')->sum('amount') - $entries->where('type', 'payment')->sum('amount'), 2
                ),
                'topOwers' => $owers->take(5)->map(fn ($amount, $name) => ['name' => $name, 'amount' => $amount])->values()->all(),
            ],
            'branches' => $business->branches()->get()->map(function ($b) use ($monthSales) {
                return ['name' => $b->name, 'sales' => round($monthSales->where('branch_id', $b->id)->sum('total'), 2)];
            })->values()->all(),
            'paymentTypes' => collect($analytics->byPayment($monthSales))
                ->union(['cash' => 0, 'gcash' => 0, 'maya' => 0, 'utang' => 0])->all(),
            'deadStock' => $products->filter(
                fn ($p) => $p->quantity > 0 && ($p->last_sold_at === null || $p->last_sold_at->lt($deadCutoff))
            )->values()->map(fn ($p) => ['name' => $p->name, 'qty' => $p->quantity, 'unit' => $p->unit])->all(),
        ];
    }

    public function answer(Business $business, string $question): string
    {
        $context = $this->buildContext($business);

        if (config('services.assistant.url') && config('services.assistant.key')) {
            $llm = $this->askExternalLlm($context, $question);
            if ($llm !== null) {
                return $llm;
            }
        }

        return $this->fallbackAnswer($context, $question);
    }

    private function askExternalLlm(array $context, string $question): ?string
    {
        try {
            $response = Http::withToken(config('services.assistant.key'))
                ->timeout(30)
                ->post(rtrim(config('services.assistant.url'), '/').'/chat/completions', [
                    'model' => config('services.assistant.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => "Business data JSON:\n".json_encode($context)."\n\nQuestion: {$question}"],
                    ],
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if ($content) {
                    return $content;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    private function systemPrompt(): string
    {
        return 'You are CashIko, a friendly business assistant for a Filipino small business owner. '
            .'Answer only using the business data provided. Use simple words, short answers, peso amounts '
            .'with the peso sign, and Taglish if the user writes in Taglish. If the data does not contain '
            .'the answer, say so. When useful, end with one short practical tip.';
    }

    private function degradePeso($n): string
    {
        return '₱'.number_format(round($n, 2), 2);
    }

    private function fallbackAnswer(array $c, string $q): string
    {
        $q = mb_strtolower($q);
        $tip = $this->tip($c);

        if (str_contains($q, 'kumita') || str_contains($q, 'profit') || str_contains($q, 'kita')) {
            return sprintf(
                'Kumita ka ng %s ngayong linggo (%s benta). Buwanang kita: %s salit na %s gastos. %s',
                $this->degradePeso($c['week']['profit']),
                $this->degradePeso($c['week']['sales']),
                $this->degradePeso($c['month']['profit']),
                $this->degradePeso($c['month']['expenses']),
                $tip
            );
        }

        if (str_contains($q, 'utang')) {
            if (! $c['utang']['topOwers']) {
                return 'Wala nang utang — malinis ang libro mo ngayon! 👏 '.$tip;
            }
            $names = collect($c['utang']['topOwers'])
                ->map(fn ($o) => $o['name'].' ('.$this->degradePeso($o['amount']).')')
                ->implode(', ');

            return sprintf(
                'Ang pinakamalaking utang sa ngayon: %s. Kabuuan ng outstanding: %s. %s',
                $names,
                $this->degradePeso($c['utang']['totalOutstanding']),
                $tip
            );
        }

        if (str_contains($q, 'maubos') || str_contains($q, 'kulang') || str_contains($q, 'stock')) {
            if (! $c['lowStock']) {
                return 'FYI: lahat ng stock okay, walang kailangan i-restock ngayon.'.$tip;
            }
            $list = collect($c['lowStock'])
                ->map(fn ($p) => sprintf('%s: %s %s na lang', $p['name'], $p['qty'], $p['unit']))
                ->implode(' · ');

            return 'Mga malapit nang maubos: '.$list.' '.$tip;
        }

        if (str_contains($q, 'best') || str_contains($q, 'mabiling')) {
            if (! $c['topProducts']) {
                return 'Wala pang benta ngayong buwan, pero siguradong magtuloy ang best seller chart!'.$tip;
            }
            $rows = collect($c['topProducts'])
                ->map(fn ($p) => sprintf('%s — %s (%s)', $p['name'], $p['qty'].' sold', $this->degradePeso($p['rev'])))
                ->implode(', ');

            return 'Best seller ngayong buwan: '.$rows.' '.$tip;
        }

        if (str_contains($q, 'kahapon') || str_contains($q, 'yesterday')) {
            return sprintf(
                'Benta kahapon: '
                    .'tingnan mo ang Daily Summary para sa exact breakdown. Benta ngayon: %s (%s transaction). %s',
                $this->degradePeso($c['today']['sales']),
                $c['today']['txns'],
                $tip
            );
        }

        if (str_contains($q, 'branch')) {
            $rows = collect($c['branches'])->sortByDesc('sales')
                ->map(fn ($b) => sprintf('%s: %s', $b['name'], $this->degradePeso($b['sales'])))
                ->implode(', ');

            return 'Sales by branch ngayong buwan: '.$rows.' '.$tip;
        }

        if (str_contains($q, 'dead') || str_contains($q, 'nabebenta') || str_contains($q, 'slow')) {
            if (! $c['deadStock']) {
                return 'Good news: walang dead stock ngayon — lahat kumikilos!'.$tip;
            }
            $list = collect($c['deadStock'])
                ->map(fn ($p) => sprintf('%s (%s %s)', $p['name'], $p['qty'], $p['unit']))
                ->implode(', ');

            return 'Mga item na hindi nabebenta (walang sale sa loob ng 30 araw): '.$list.' '.$tip;
        }

        return sprintf(
            'Ito ang sitwasyon ngayon: benta ngayong buwan %s, gastos %s, utang outstanding %s. '
                .'Ang benta ngayong linggo ay %s (%s kita). Tungkol sa imong tanong na "%s", '
                .'wala akong nakitang datos dito — subukan ang apat na apat na bullet sa itaas (kumita, utang, stock, best seller). '
                .'%s',
            $this->degradePeso($c['month']['sales']),
            $this->degradePeso($c['month']['expenses']),
            $this->degradePeso($c['utang']['totalOutstanding']),
            $this->degradePeso($c['week']['sales']),
            $this->degradePeso($c['week']['profit']),
            $q,
            $tip
        );
    }

    private function tip(array $c): string
    {
        if ($c['lowStock']) {
            return 'Tip: i-restock ang mabibili habang available — bago maubos.';
        }
        if ($c['utang']['topOwers']) {
            return 'Tip: subukang pumunta sa Utang page at mag-send ng gentle reminder ngayong linggo.';
        }

        return 'Tip: check ang Benta page paminsan-minsitan, ang pinaka-mahalaga ay ang Kita bawat araw.';
    }
}
