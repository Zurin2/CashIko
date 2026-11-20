<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Spec §15 verify checklist, automated:
 * onboarding → log a sale → utang with payment → restock → totals.
 */
class CashIkoFlowTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(): Business
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/onboarding', [
            'name' => 'Tindahan ni Aling Nena',
            'type' => 'Sari-sari store',
            'location' => 'Cagayan de Oro',
            'multiBranch' => true,
            'extraBranches' => ['Stall 2 - Palengke'],
        ])->assertRedirect('/home');

        $business = $user->business()->first();

        $this->seedProducts($business);

        return $business;
    }

    /** Three fixture SKUs: enough depth for sale, oversell, void and restock cases. */
    private function seedProducts(Business $business): void
    {
        $branchId = $business->branches()->first()->id;

        foreach ([
            ['name' => 'Kape 3-in-1', 'unit' => 'sachet', 'quantity' => 24, 'cost_price' => 5, 'selling_price' => 8],
            ['name' => 'Sabon Bar', 'unit' => 'pcs', 'quantity' => 10, 'cost_price' => 20, 'selling_price' => 28],
            ['name' => 'Coke 1.5L', 'unit' => 'bottle', 'quantity' => 4, 'cost_price' => 55, 'selling_price' => 70],
        ] as $row) {
            $business->products()->create($row + [
                'branch_id' => $branchId,
                'low_stock_threshold' => $business->low_stock_threshold,
            ]);
        }
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
        $user = User::factory()->create();

        $this->actingAs($user)->post('/onboarding', [
            'name' => 'Tindahan ni Aling Nena',
            'type' => 'Sari-sari store',
            'location' => 'Cagayan de Oro',
            'multiBranch' => true,
            'extraBranches' => ['Stall 2 - Palengke'],
        ])->assertRedirect('/home');

        $business = $user->business()->first();

        $this->assertSame('Tindahan ni Aling Nena', $business->name);
        $this->assertSame('Cagayan de Oro', $business->location);
        $this->assertSame('basic', $business->plan);
        $this->assertTrue($business->trial_ends_at->isFuture());
        $this->assertSame(2, $business->branches()->count());
        $this->assertSame('Main', $business->branches()->first()->name);
        // A new business starts genuinely empty — no demo records are seeded.
        $this->assertSame(0, $business->products()->count());
        $this->assertSame(0, $business->sales()->count());
        $this->assertSame(0, $business->utangEntries()->count());
    }

    public function test_cash_sale_decrements_stock_and_creates_movement(): void
    {
        $business = $this->onboard();
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
        $business = $this->onboard();
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
            'amount' => round($utangSum / 2, 2),
            'date' => now()->toDateString(),
        ])->assertRedirect('/utang');

        $balance = $business->utangEntries()
            ->where('customer_id', $customer->id)
            ->get()
            ->reduce(fn ($acc, $e) => $acc + ($e->type === 'utang' ? $e->amount : -$e->amount));

        $this->assertEqualsWithDelta($utangSum - round($utangSum / 2, 2), (float) $balance, 0.001);
    }

    public function test_payment_cannot_exceed_utang_balance(): void
    {
        $business = $this->onboard();
        $product = $business->products()->where('quantity', '>', 1)->first();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_type' => 'utang',
            'new_customer' => ['name' => 'Tolits Fabriquio'],
        ])->assertRedirect('/sales');

        $customer = $business->customers()->where('name', 'Tolits Fabriquio')->firstOrFail();
        $entriesBefore = $business->utangEntries()->count();

        $this->post('/utang/payment', [
            'customer_id' => $customer->id,
            'amount' => 99999,
            'date' => now()->toDateString(),
        ])->assertSessionHas('error', fn ($v) => str_contains($v, 'larger than the utang'));

        // No payment entry was created; the ledger stays untouched.
        $this->assertSame($entriesBefore, $business->utangEntries()->count());
    }

    public function test_void_utang_sale_removes_ledger_entry(): void
    {
        $business = $this->onboard();
        $product = $business->products()->where('quantity', '>', 3)->first();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_type' => 'utang',
            'new_customer' => ['name' => 'Tolits Fabriquio'],
        ])->assertRedirect('/sales');

        $sale = Sale::latest('id')->first();
        $customer = $business->customers()->where('name', 'Tolits Fabriquio')->firstOrFail();

        // The debt exists while the sale is active.
        $this->assertTrue($business->utangEntries()->where('sale_id', $sale->id)->exists());

        $this->post("/sales/{$sale->id}/void")->assertSessionHas('success');

        // Voiding the sale clears the debt — the customer owes nothing for it.
        $this->assertFalse($business->utangEntries()->where('sale_id', $sale->id)->exists());
        $balance = $business->utangEntries()->where('customer_id', $customer->id)
            ->get()->reduce(fn ($acc, $e) => $acc + ($e->type === 'utang' ? $e->amount : -$e->amount), 0);
        $this->assertEqualsWithDelta(0, (float) $balance, 0.001);
    }

    public function test_sale_blocks_oversell(): void
    {
        $business = $this->onboard();
        $product = $business->products()->orderBy('quantity')->first();

        $this->actingAs($business->owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => $product->quantity + 100]],
            'payment_type' => 'cash',
        ])->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Not enough stock');
        });
    }

    public function test_restock_updates_stock_and_logs_expense(): void
    {
        $business = $this->onboard();
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
        $business = $this->onboard();
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

    public function test_payment_methods_seed_and_qr_upload(): void
    {
        $business = $this->onboard();
        $owner = $business->owner;

        $this->actingAs($owner)->get('/settings')->assertOk();

        $codes = PaymentMethod::where('business_id', $business->id)->pluck('code')->all();
        $this->assertSame(['cash', 'gcash', 'maya'], $codes);

        // Upload a QR image for GCash (§ POS scan panel).
        Storage::fake('private');
        $gcash = PaymentMethod::where('business_id', $business->id)->where('code', 'gcash')->first();

        $this->actingAs($owner)->post("/settings/payment-methods/{$gcash->id}", [
            'label' => 'GCash',
            'account_name' => 'Aling Nena',
            'account_number' => '09171234567',
            'is_active' => '1',
            'qr' => File::image('qr.png', 60, 60),
        ])->assertRedirect('/settings');

        $gcash->refresh();
        $this->assertNotNull($gcash->qr_path);
        $this->assertSame('Aling Nena', $gcash->account_name);
        Storage::disk('private')->assertExists($gcash->qr_path);

        // Custom wallet method can be added and removed.
        $this->actingAs($owner)->post('/settings/payment-methods', ['label' => 'ShopeePay'])
            ->assertRedirect('/settings');
        $custom = PaymentMethod::where('business_id', $business->id)->where('label', 'ShopeePay')->first();
        $this->assertNotNull($custom);
        $this->assertStringStartsWith('custom-', $custom->code);

        $this->actingAs($owner)->delete("/settings/payment-methods/{$custom->id}")
            ->assertRedirect('/settings');
        $this->assertDatabaseMissing('payment_methods', ['id' => $custom->id]);
    }
}
