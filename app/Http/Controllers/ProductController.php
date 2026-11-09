<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\StockMovement;
use App\Support\Period;
use App\Support\Photo;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Stock page (§8.6) + product CRUD + restock flow (§7.6). */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        $search = $request->query('search');
        $status = $request->query('status', 'all');
        $category = $request->query('category', 'all');

        $products = $business->products()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($category !== 'all', fn ($q) => $q->where('category', $category))
            ->orderBy('name')
            ->get();

        $deadCutoff = now()->subDays(30);

        $products = $products->filter(function ($p) use ($status, $deadCutoff) {
            $state = match (true) {
                $p->quantity <= 0 => 'out',
                $p->quantity <= $p->low_stock_threshold => 'low',
                default => 'okay',
            };
            $dead = $p->quantity > 0 && ($p->last_sold_at === null || $p->last_sold_at->lt($deadCutoff));

            return match ($status) {
                'low' => $state === 'low',
                'out' => $state === 'out',
                'dead' => $dead,
                default => true,
            };
        })->values();

        $branches = $business->branches()->get();

        return Inertia::render('Stock/Index', [
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'unit' => $p->unit,
                'quantity' => $p->quantity,
                'cost_price' => $p->cost_price,
                'selling_price' => $p->selling_price,
                'low_stock_threshold' => $p->low_stock_threshold,
                'expiration_date' => $p->expiration_date?->toDateString(),
                'photo_url' => Photo::signedUrl($p->photo_path),
                'branch_id' => $p->branch_id,
                'branch_name' => $branches->find($p->branch_id)?->name,
                'status' => match (true) {
                    $p->quantity <= 0 => 'out',
                    $p->quantity <= $p->low_stock_threshold => 'low',
                    default => 'okay',
                },
                'is_dead_stock' => $p->quantity > 0 && ($p->last_sold_at === null || $p->last_sold_at->lt($deadCutoff)),
            ]),
            'categories' => $business->products()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'filters' => [
                'search' => (string) $search,
                'status' => $status,
                'category' => $category,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'category' => 'nullable|string|max:60',
            'unit' => 'required|in:pcs,pack,bottle,kg,sachet,stick,box,loaf,kilo',
            'quantity' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|integer',
            'photo_path' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
        ]);

        [$all, $fallback] = Period::branch($business, null);
        $data['branch_id'] = collect([$data['branch_id'] ?? null, $fallback])->first(fn ($id) => $business->branches()->where('id', $id)->exists()
        );
        $data['low_stock_threshold'] = $data['low_stock_threshold'] ?? $business->low_stock_threshold;

        $product = $business->products()->create($data);

        if ($product->quantity > 0) {
            StockMovement::create([
                'business_id' => $business->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'branch_id' => $product->branch_id,
                'type' => 'restock',
                'quantity_change' => $product->quantity,
                'cost_price' => $product->cost_price,
                'date' => now()->toDateString(),
            ]);
        }

        return redirect()->to('/stock')->with('success', "Nadagdag sa stock: {$product->name}.");
    }

    public function update(Request $request, $productId)
    {
        $business = $request->user()->business;
        $product = $business->products()->findOrFail($productId);

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'category' => 'nullable|string|max:60',
            'unit' => 'required|in:pcs,pack,bottle,kg,sachet,stick,box,loaf,kilo',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|integer',
            'photo_path' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
        ]);

        $product->update($data);

        return redirect()->to('/stock')->with('success', 'Na-update ang product.');
    }

    public function destroy(Request $request, $productId)
    {
        $product = $request->user()->business->products()->findOrFail($productId);
        $product->delete();

        return redirect()->to('/stock')->with('error', "Deleted {$product->name}.");
    }

    /** Section 7.6: stock up + new cost + restock movement + Restock/Supplies expense. */
    public function restock(Request $request, $productId)
    {
        $business = $request->user()->business;
        $product = $business->products()->findOrFail($productId);

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1',
            'cost_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:120',
        ]);

        [$all, $fallback] = Period::branch($business, $request->query('branch'));
        $branchId = $product->branch_id ?: $fallback;

        $product->increment('quantity', $data['quantity']);
        $product->update(['cost_price' => $data['cost_price']]);

        StockMovement::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'branch_id' => $branchId,
            'type' => 'restock',
            'quantity_change' => $data['quantity'],
            'cost_price' => $data['cost_price'],
            'supplier' => $data['supplier'] ?? null,
            'date' => now()->toDateString(),
        ]);

        Expense::create([
            'business_id' => $business->id,
            'branch_id' => $branchId,
            'amount' => round($data['quantity'] * $data['cost_price'], 2),
            'category' => 'Restock/Supplies',
            'date' => now()->toDateString(),
            'note' => "Restock {$product->name}".(isset($data['supplier']) ? " · {$data['supplier']}" : ''),
        ]);

        return redirect()->to('/stock')->with('success', "Na-restock ang {$product->name} — naka-log din ang gastos.");
    }

    /** Private photo upload → path; the product stores photo_path. */
    public function uploadPhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);

        $path = $request->file('photo')->store('products', 'private');

        return response()->json(['photo_path' => $path]);
    }
}
