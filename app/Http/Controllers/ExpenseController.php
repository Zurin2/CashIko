<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\Period;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Gastos (§8.8): chips + list + add sheet, month scope by default. */
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        [$branchId, $fallback] = Period::branch($business, $request->query('branch'));

        $start = Period::resolve($request->query('range', 'month')) ?: Period::month();
        $category = $request->query('category', 'all');

        $expenses = $business->expenses()
            ->when($branchId, fn ($q) => $q->whereBranchId($branchId))
            ->where('date', '>=', $start->toDateString())
            ->when($category !== 'all', fn ($q) => $q->where('category', $category))
            ->orderByDesc('date')->orderByDesc('id')
            ->limit(500)
            ->get();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses->map(fn ($e) => [
                'id' => $e->id,
                'amount' => $e->amount,
                'category' => $e->category,
                'custom_category' => $e->custom_category,
                'date' => $e->date,
                'note' => $e->note,
                'branch_id' => $e->branch_id,
            ]),
            'total' => $expenses->sum('amount'),
            'categories' => Expense::CATEGORIES,
            'filters' => ['range' => $request->query('range', 'month'), 'category' => $category],
        ]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.5',
            'category' => 'required|in:'.implode(',', Expense::CATEGORIES),
            'custom_category' => 'nullable|required_if:category,Other|string|max:60',
            'date' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        [$all, $fallback] = Period::branch($business, $request->query('branch'));

        $business->expenses()->create($data + ['branch_id' => $fallback]);

        return redirect()->to('/expenses')->with('success', sprintf('Naitala ang gastos: ₱%s', number_format($data['amount'], 2)));
    }

    public function destroy(Request $request, $expenseId)
    {
        $expense = $request->user()->business->expenses()->findOrFail($expenseId);
        $expense->delete();

        return redirect()->to('/expenses')->with('error', 'Deleted expense.');
    }
}
