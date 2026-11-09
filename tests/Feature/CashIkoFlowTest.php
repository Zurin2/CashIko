<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec §15 verify checklist, automated:
 * onboarding → sample data → log a sale → utang with payment → restock → totals.
 */
class CashIkoFlowTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(bool $sample = true): Business
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/onboarding', [
            'name' => 'Tindahan ni Aling Nena',
            'type' => 'Sari-sari store',
            'location' => 'Cagayan de Oro',
            'multiBranch' => true,
            'extraBranches' => ['Stall 2 - Palengke'],
            'withSample' => $sample,
        ])->assertRedirect('/home');

        return $user->business()->first();
    }

    public function test_landing_renders_for_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('CashIko');
    }

    public function test_user_without_business_is_sent_to_onboarding(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/home')->assertRedirect('/onboarding');
    }

    public function test_onboarding_creates_business_and_branches(): void
    {
        $business = $this->onboard(false);

        $this->assertSame('Cagayan de Oro', $business->location);
        $this->assertSame('basic', $business->plan);
        $this->assertTrue($business->trial_ends_at->isFuture());
        $this->assertSame(2, $business->branches()->count());
        $this->assertSame('Main', $business->branches()->first()->name);
    }

    public function test_sample_data_loads_demo_dataset(): void
    {
        $business = $this->onboard(true);

        $this->assertTrue($business->products()->count() >= 20);
        $this->assertTrue($business->customers()->count() === 12);
        $this->assertTrue($business->sales()->active()->count() > 30);
        $this->assertTrue($business->utangEntries()->where('type', 'utang')->count() > 0);
        $this->assertTrue($business->expenses()->count() > 0);
        $this->assertTrue($business->chatMessages()->count() === 2);
    }

    public function test_cash_sale_decrements_stock_and_creates_movement(): void
    {
        $business = $this->onboard(true);
        $product = $business->products()->where('quantity', '>', 2)->first();
        $movementsBefore = $business->stockMovements()->count();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_type' => 'cash',
        ])->assertRedirect('/sales');

        $sale = Sale::latest('id')->first();
        $this->assertSame('cash', $sale->payment_type);
        $this->assertEqualsWithDelta((float) $product->selling_price * 2, (float) $sale->total, 0.001);
        $this->assertEqualsWithDelta((float) $product->quantity - 2, (float) $product->fresh()->quantity, 0.001);
        $this->assertDatabaseCount('stock_movements', $movementsBefore + 1);
    }

    public function test_utang_sale_creates_ledger_entry_and_payment_clears_balance(): void
    {
        $business = $this->onboard(true);
        $product = $business->products()->where('quantity', '>', 1)->first();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_type' => 'utang',
            'new_customer' => ['name' => 'Tolits Fabriquio', 'phone' => '09171234567'],
        ])->assertRedirect('/sales');

        $customer = $business->customers()->where('name', 'Tolits Fabriquio')->firstOrFail();

        $utangSum = $business->utangEntries()->where('customer_id', $customer->id)->where('type', 'utang')->sum('amount');
        $this->assertGreaterThan(0, $utangSum);
        // The sale created a ledger entry linked back to the sale (§7.7 step 5).
        $this->assertDatabaseCount('utang_entries', $business->utangEntries()->count());

        $this->post('/utang/payment', [
            'customer_id' => $customer->id,
            'amount' => 100,
            'date' => now()->toDateString(),
        ])->assertRedirect('/utang');

        $balance = $business->utangEntries()
            ->where('customer_id', $customer->id)
            ->get()
            ->reduce(fn ($acc, $e) => $acc + ($e->type === 'utang' ? $e->amount : -$e->amount));

        $this->assertEqualsWithDelta($utangSum - 100, (float) $balance, 0.001);
    }

    public function test_sale_blocks_oversell(): void
    {
        $business = $this->onboard(true);
        $product = $business->products()->orderBy('quantity')->first();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => $product->quantity + 100]],
            'payment_type' => 'cash',
        ])->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Kulang ang stock');
        });
    }

    public function test_restock_updates_stock_and_logs_expense(): void
    {
        $business = $this->onboard(true);
        $product = $business->products()->first();
        $before = $product->quantity;
        $expensesBefore = $business->expenses()->count();

        $this->actingAs($business->owner)
            ->post("/products/{$product->id}/restock", [
                'quantity' => 5,
                'cost_price' => 42,
                'supplier' => 'Divisoria',
            ])->assertRedirect('/stock');

        $this->assertEqualsWithDelta($before + 5, (float) $product->fresh()->quantity, 0.001);
        $this->assertSame($expensesBefore + 1, $business->expenses()->count());

        $expense = $business->expenses()->latest('id')->first();
        $this->assertSame('Restock/Supplies', $expense->category);
        $this->assertEqualsWithDelta(5 * 42, (float) $expense->amount, 0.001);
    }

    public function test_void_sale_restores_stock(): void
    {
        $business = $this->onboard(true);
        $product = $business->products()->where('quantity', '>', 3)->first();
        $qty = (float) $product->quantity;

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_type' => 'cash',
        ])->assertRedirect('/sales');

        $sale = Sale::latest('id')->first();
        $this->assertEqualsWithDelta($qty - 2, (float) $product->fresh()->quantity, 0.001);

        $this->post("/sales/{$sale->id}/void")->assertSessionHas('success');

        $this->assertEqualsWithDelta($qty, (float) $product->fresh()->quantity, 0.001);
        $this->assertSame('void', $sale->fresh()->status);
    }
}
