<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\StockMovement;
use App\Models\UtangEntry;
use App\Support\Payment;
use App\Support\Period;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Sales + POS (§8.4, §8.5, §7.7). The multi-step sale sequence now runs inside
 * one DB transaction — the spec's non-atomic client-side limitation is fixed:
 * prices/costs are re-verified server-side and stock is checked under lock.
 */
class SaleController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        $range = $request->query('range', 'week');
        $start = Period::resolve($range);

        $sales = $business->sales()
            ->where('status', 'active')
            ->when($start, fn ($q) => $q->where('date_time', '>=', $start))
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->when($payment = $request->query('payment'), function ($q) use ($payment) {
                if ($payment !== 'all') {
                    $q->where('payment_type', $payment);
                }
            })
            ->orderByDesc('date_time')
            ->limit(500)
            ->get();

        $products = $business->products()->orderBy('name')->get();
        $customers = $business->customers()->orderBy('name')->get();
        $branches = $business->branches()->get();

        return Inertia::render('Sales/Index', [
            'sales' => $sales->map(fn ($s) => [
                'id' => $s->id,
                'date_time' => $s->date_time->toISOString(),
                'payment_type' => $s->payment_type,
                'total' => $s->total,
                'total_cost' => $s->total_cost,
                'customer_name' => $s->customer_name,
                'status' => $s->status,
                'items' => $s->items,
                'branch_id' => $s->branch_id,
                'branch_name' => $branches->find($s->branch_id)?->name,
            ]),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'unit' => $p->unit,
                'selling_price' => $p->selling_price,
                'cost_price' => $p->cost_price,
                'photo_url' => Photo::signedUrl($p->photo_path),
                'branch_id' => $p->branch_id,
            ]),
            'customers' => $customers->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone,
            ]),
            'filters' => ['range' => $range, 'payment' => $payment ?: 'all'],
        ]);
    }

    /** Section 7.7 transaction. `items` = [{product_id, quantity}]. */
    public function store(Request $request)
    {
        $business = $request->user()->business;

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:1',
            'payment_type' => 'required|in:cash,gcash,maya,utang',
            'customer_id' => 'nullable|integer',
            'new_customer.name' => 'nullable|string|max:120',
            'new_customer.phone' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer',
            'due_date' => 'nullable|date|after_or_equal:today',
            'note' => 'nullable|string|max:500',
        ];
        $data = $request->validate($rules);
        if ($data['payment_type'] === 'utang') {
            $rules['customer_id'] = Rule::requiredIf(empty($data['new_customer']['name'] ?? null)).'|integer';
            $data = $request->validate($rules);
        }

        [, $fallback] = Period::branch($business, null);
        $branchId = collect([$data['branch_id'] ?? null, $fallback])->first(fn ($id) => $business->branches()->where('id', $id)->where('is_active', true)->exists()
        );

        try {
            $sale = DB::transaction(function () use ($business, $data, $branchId) {
                // Lock products so concurrent POS terminals cannot oversell.
                $products = $business->products()
                    ->whereIn('id', collect($data['items'])->pluck('product_id'))
                    ->lockForUpdate()
                    ->get()->keyBy('id');

                if (count($products) !== count(collect($data['items'])->pluck('product_id')->unique())) {
                    throw new \InvalidArgumentException('Some items no longer exist. Ibalik lang ang mga ito sa cart.');
                }

                foreach ($data['items'] as $line) {
                    $product = $products[$line['product_id']];
                    if ($line['quantity'] > $product->quantity) {
                        throw new \InvalidArgumentException(sprintf(
                            'Kulang ang stock — %s: %s %s na lang ang natitira',
                            $product->name, $product->quantity, $product->unit
                        ));
                    }
                }

                // Denormalized items: name/unit price/unit cost copied at sale time.
                $items = [];
                $total = 0;
                $totalCost = 0;
                foreach ($data['items'] as $line) {
                    $product = $products[$line['product_id']];
                    $items[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => (float) $line['quantity'],
                        'unit_price' => (float) $product->selling_price,
                        'unit_cost' => (float) $product->cost_price,
                    ];
                    $total += $product->selling_price * $line['quantity'];
                    $totalCost += $product->cost_price * $line['quantity'];
                }

                // §7.7 step 1: a new customer name creates the Customer
                $customerId = $data['customer_id'] ?? ($data['new_customer']['name'] ?? null
                    ? $business->customers()->create([
                        'name' => $data['new_customer']['name'],
                        'phone' => $data['new_customer']['phone'] ?? null,
                    ])->id
                    : null);

                if ($data['payment_type'] === 'utang' && ! $customerId) {
                    throw new \InvalidArgumentException('Utang needs an existing customer or a new customer name.');
                }

                $customer = $customerId ? $business->customers()->find($customerId) : null;

                // Section 7.7 step 2
                $sale = $business->sales()->create([
                    'branch_id' => $branchId,
                    'customer_id' => $customer?->id,
                    'customer_name' => $customer?->name,
                    'date_time' => now(),
                    'payment_type' => $data['payment_type'],
                    'total' => round($total, 2),
                    'total_cost' => round($totalCost, 2),
                    'status' => 'active',
                    'items' => $items,
                    'note' => $data['note'] ?? null,
                ]);

                // Section 7.7 steps 3-4
                foreach ($items as $line) {
                    $product = $products[$line['product_id']];
                    $product->decrement('quantity', $line['quantity']);
                    $product->update(['last_sold_at' => $sale->date_time]);
                    StockMovement::create([
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'branch_id' => $branchId,
                        'type' => 'sale',
                        'quantity_change' => -$line['quantity'],
                        'cost_price' => $line['unit_cost'],
                        'date' => $sale->date_time->format('Y-m-d'),
                    ]);
                }

                // Section 7.7 step 5: debt
                if ($data['payment_type'] === 'utang' && $customer) {
                    $dueDate = $data['due_date'] ?? now()->addDays($business->default_due_days)->toDateString();
                    UtangEntry::create([
                        'business_id' => $business->id,
                        'branch_id' => $branchId,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'sale_id' => $sale->id,
                        'type' => 'utang',
                        'amount' => $sale->total,
                        'date' => now()->toDateString(),
                        'due_date' => $dueDate,
                        'recorded_by_id' => $business->user_id,
                    ]);
                }

                return $sale;
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = Payment::label($sale->payment_type);

        return redirect()->to('/sales')->with('success', sprintf('Naitala na ang benta! %s · ₱%s', $label, number_format($sale->total, 2)));
    }

    /** Section 7.8: void sale, restore stock, write void movements. */
    public function void(Request $request, $saleId)
    {
        $business = $request->user()->business;
        $sale = $business->sales()->where('status', 'active')->find($saleId);
        if (! $sale) {
            return back()->with('error', 'Sale not found or already voided.');
        }

        DB::transaction(function () use ($sale, $business) {
            foreach ($sale->items as $line) {
                $product = $business->products()->find($line['product_id']);
                if ($product) {
                    $product->increment('quantity', $line['quantity'] ?? 0);
                    StockMovement::create([
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'branch_id' => $sale->branch_id,
                        'type' => 'void',
                        'quantity_change' => $line['quantity'] ?? 0,
                        'cost_price' => $line['unit_cost'] ?? 0,
                        'date' => now()->toDateString(),
                    ]);
                }
            }
            $sale->update(['status' => 'void']);
        });

        return back()->with('success', 'Voided the sale. Stock has returned.');
    }
}
