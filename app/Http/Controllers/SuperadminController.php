<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\UtangEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Superadmin (§11.2, §8.16): service-role pattern from the spec becomes a
 * Blade-free gate — a user must be is_superadmin && role "admin"; queries
 * run without the RLS filter because this is the one cross-business view.
 */
class SuperadminController extends Controller
{
    public function index(Request $request)
    {
        if (! Gate::allows('view-superadmin', $request->user())) {
            return Inertia::render('Superadmin/Index', ['forbidden' => true]);
        }

        $users = User::orderByDesc('created_at')->get();
        $businesses = Business::with(['branches', 'owner'])->orderByDesc('created_at')->get();
        $sales = Sale::all();
        $products = Product::all();
        $utang = UtangEntry::all();
        $expenses = Expense::all();

        $businessStats = $businesses->map(function ($b) use ($sales, $products, $utang, $expenses) {
            $bSales = $sales->where('business_id', $b->id)->where('status', 'active');
            $salesTotal = $bSales->sum('total');
            $costTotal = $bSales->sum('total_cost');
            $expensesTotal = $expenses->where('business_id', $b->id)->sum('amount');
            $utangOut = $utang->where('business_id', $b->id)->where('type', 'utang')->sum('amount')
                - $utang->where('business_id', $b->id)->where('type', 'payment')->sum('amount');

            $lastSale = $bSales->sortByDesc('date_time')->first();

            return [
                'id' => $b->id,
                'name' => $b->name,
                'type' => $b->type,
                'location' => $b->location,
                'plan' => $b->plan,
                'owner_email' => $b->owner?->email,
                'sales_count' => $bSales->count(),
                'sales_total' => round($salesTotal, 2),
                'profit' => round($salesTotal - $costTotal - $expensesTotal, 2),
                'utang_outstanding' => round($utangOut, 2),
                'products_count' => $products->where('business_id', $b->id)->count(),
                'low_stock' => $products->where('business_id', $b->id)
                    ->filter(fn ($p) => $p->quantity <= $p->low_stock_threshold)->count(),
                'last_sale' => $lastSale?->date_time?->toISOString(),
                'created_date' => $b->created_at->toISOString(),
            ];
        })->sortByDesc('sales_total')->values();

        return Inertia::render('Superadmin/Index', [
            'forbidden' => false,
            'users' => $users->map(function ($u) use ($businesses) {
                $business = $businesses->firstWhere('user_id', $u->id);

                return [
                    'id' => $u->id,
                    'email' => $u->email,
                    'full_name' => $u->name,
                    'is_superadmin' => $u->is_superadmin,
                    'created_date' => $u->created_at->toISOString(),
                    'business_name' => $business?->name,
                ];
            }),
            'businesses' => $businessStats,
            'totals' => [
                'users' => $users->count(),
                'businesses' => $businesses->count(),
                'sales_total' => round($sales->where('status', 'active')->sum('total'), 2),
                'utang_outstanding' => round(
                    $utang->where('type', 'utang')->sum('amount') - $utang->where('type', 'payment')->sum('amount'), 2
                ),
                'products' => $products->count(),
            ],
        ]);
    }
}
