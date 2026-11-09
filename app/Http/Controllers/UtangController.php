<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\UtangEntry;
use App\Support\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Utang ledger (§8.7) with §7.4 balances/statuses and §7.9 payment routing. */
class UtangController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        // Spec keeps UtangEntry all-time reads business-wide on some screens;
        // Utang page itself is branch-scoped when a branch is selected.
        $entries = $business->utangEntries()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        $customers = $business->customers()->orderBy('name')->get()
            ->filter(fn ($c) => $branchId ? $entries->contains('customer_id', $c->id) : true);

        $branches = $business->branches()->get();
        $today = now()->toDateString();

        $rows = $customers->map(function ($c) use ($entries, $today) {
            $mine = $entries->where('customer_id', $c->id);
            $utangSum = $mine->where('type', 'utang')->sum('amount');
            $paidSum = $mine->where('type', 'payment')->sum('amount');
            $balance = round($utangSum - $paidSum, 2);
            $oldestDue = $mine->where('type', 'utang')
                ->filter(fn ($e) => $e->due_date !== null)
                ->map(fn ($e) => $e->due_date->format('Y-m-d'))
                ->min();

            $status = match (true) {
                $balance <= 0 => ['label' => 'Up to date', 'color' => 'green'],
                $oldestDue !== null && $oldestDue < $today => ['label' => 'Overdue', 'color' => 'red'],
                $oldestDue !== null && $oldestDue <= now()->addDays(3)->toDateString() => ['label' => 'Due soon', 'color' => 'orange'],
                $balance > 0 => ['label' => 'Outstanding', 'color' => 'blue'],
                default => ['label' => 'Up to date', 'color' => 'green'],
            };

            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'tag' => $c->tag,
                'balance' => $balance,
                'utang_sum' => $utangSum,
                'paid_sum' => $paidSum,
                'oldest_due' => $oldestDue,
                'status' => $status,
            ];
        });

        $totals = [
            'outstanding' => round($rows->sum('balance'), 2),
            'collected_this_month' => round($entries
                ->where('type', 'payment')
                ->filter(fn ($e) => $e->date->isCurrentMonth())
                ->sum('amount'), 2),
        ];

        return Inertia::render('Utang/Index', [
            'customers' => $rows->values(),
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'customer_id' => $e->customer_id,
                'customer_name' => $e->customer_name,
                'type' => $e->type,
                'amount' => (float) $e->amount,
                'date' => $e->date->toDateString(),
                'due_date' => $e->due_date?->toDateString(),
                'note' => $e->note,
                'branch_id' => $e->branch_id,
            ]),
            'totals' => $totals,
            'branches' => $branches,
        ]);
    }

    /** §7.9: standalone utang record; the POS also writes here via sale_id link (§7.7). */
    public function storeUtang(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'new_customer.name' => 'nullable|string|max:120',
            'new_customer.phone' => 'nullable|string|max:40',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'branch_id' => 'nullable|integer',
            'note' => 'nullable|string|max:500',
        ]);

        [$all, $fallback] = Period::branch($business, $request->query('branch'));
        $branchId = ($data['branch_id'] ?? null) ?: $fallback;

        $customer = $data['customer_id'] ? $business->customers()->find($data['customer_id'])
            : $business->customers()->create([
                'name' => $data['new_customer']['name'] ?? 'Walk-in customer',
                'phone' => $data['new_customer']['phone'] ?? null,
            ]);

        if (! $customer) {
            return back()->with('error', 'Customer not found.');
        }

        UtangEntry::create([
            'business_id' => $business->id,
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'type' => 'utang',
            'amount' => $data['amount'],
            'date' => now()->toDateString(),
            'due_date' => $data['due_date'] ?? now()->addDays($business->default_due_days)->toDateString(),
            'note' => $data['note'] ?? null,
            'recorded_by_id' => $business->user_id,
        ]);

        return redirect()->to('/utang')->with('success', sprintf('Naitala ang utang ni %s: ₱%s', $customer->name, number_format($data['amount'], 2)));
    }

    /** Payment attribution: viewing All Branches credits the branch owed the most (§7.9). */
    public function storePayment(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'customer_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.5',
            'date' => 'required|date',
            'branch_id' => 'nullable|integer',
            'note' => 'nullable|string|max:500',
        ]);

        $customer = $business->customers()->findOrFail($data['customer_id']);

        [$all, $fallback] = Period::branch($business, $request->query('branch'));
        $branchId = ($data['branch_id'] ?? null) ?: $this->branchOwedMost($business, $customer) ?: $fallback;

        UtangEntry::create([
            'business_id' => $business->id,
            'branch_id' => $branchId,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'type' => 'payment',
            'amount' => $data['amount'],
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
            'recorded_by_id' => $business->user_id,
        ]);

        return redirect()->to('/utang')->with('success', 'Naitala na ang bayad. Salamat!');
    }

    private function branchOwedMost($business, $customer): ?int
    {
        $rows = $business->utangEntries()
            ->where('customer_id', $customer->id)
            ->selectRaw('branch_id, SUM(CASE WHEN type = ? THEN amount ELSE 0 END) - SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as owed', ['utang', 'payment'])
            ->groupBy('branch_id')->orderByDesc('owed')->get();

        return $rows->first()?->branch_id;
    }

    public function setTag(Request $request, $customerId)
    {
        $data = $request->validate(['tag' => 'required|in:none,trusted,watch']);

        $customer = $request->user()->business->customers()->findOrFail($customerId);
        $customer->update(['tag' => $data['tag']]);

        return back()->with('success', "Naka-save: {$customer->name} → {$data['tag']}.");
    }

    /** §7.10 template; Send logs a `sent` reminder (v1 has no real SMS). */
    public function storeReminder(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'customer_id' => 'required|integer',
            'message' => 'required|string|max:1000',
            'tone' => 'in:gentle,firm',
        ]);

        $customer = $business->customers()->findOrFail($data['customer_id']);
        Reminder::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'message' => $data['message'],
            'tone' => $data['tone'] ?? $business->default_reminder_tone,
            'status' => 'sent',
            'date' => now()->toDateString(),
        ]);

        return back()->with('success', 'Na-send na ang reminder (saved as sent).');
    }
}
