<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\UtangEntry;
use App\Support\Period;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Demo data (spec §14). Idempotent: only fills what the business is missing,
 * so it can run at onboarding (Start with sample data) or from Settings.
 */
class SampleDataService
{
    /** Dead-stock demo items: monitored but never sold by the generator. */
    private array $deadProductIds = [];

    public function load(Business $business): void
    {
        DB::transaction(function () use ($business) {
            $this->loadBranches($business);
            $this->loadProducts($business);
            $this->loadCustomers($business);
            $this->loadSalesAndUtang($business);
            $this->loadExpenses($business);
            $this->loadChat($business);
        });
    }

    private function loadBranches(Business $business): void
    {
        foreach (['Stall 2 - Palengke', 'Stall 3 - Terminal'] as $name) {
            $business->branches()->firstOrCreate(compact('name'), ['location' => $business->location]);
        }
    }

    private function randomBranchId(Business $business): ?int
    {
        $ids = $business->branches()->pluck('id')->all();

        return $ids ? Arr::random($ids) : null;
    }

    private function loadProducts(Business $business): void
    {
        if ($business->products()->exists()) {
            return;
        }

        $catalog = [
            ['Coke 1.5L', 'Drinks', 'bottle', 45, 60, 24],
            ['Sprite 800ml', 'Drinks', 'bottle', 28, 40, 30],
            ['Royal Tru-Orange 12oz', 'Drinks', 'bottle', 22, 35, 24],
            ['Water 500ml', 'Drinks', 'bottle', 10, 15, 96],
            ['Piattos Cheese', 'Snacks', 'pack', 18, 25, 40],
            ['Chippy BBQ', 'Snacks', 'pack', 18, 25, 35],
            ['Clubhouse Nori', 'Snacks', 'pack', 20, 28, 30],
            ['Sky Flakes', 'Snacks', 'pack', 22, 30, 25],
            ['Lucky Me Pancit Canton', 'Noodles', 'sachet', 12, 16, 72],
            ['Nissin Seafood', 'Noodles', 'sachet', 14, 18, 60],
            ['Rice (per kilo)', 'Pantry', 'kilo', 48, 55, 50],
            ['Brown Sugar 500g', 'Pantry', 'pack', 25, 35, 12],
            ['Cooking Oil 1L', 'Pantry', 'bottle', 90, 115, 8],
            ['Eggs (per piece)', 'Pantry', 'pcs', 7, 10, 60],
            ['Pandesal Bundle (10pcs)', 'Bakery', 'pack', 45, 60, 15],
            ['Shampoo Sachet', 'Toiletries', 'sachet', 5, 8, 100],
            ['Soap (Bath)', 'Toiletries', 'pcs', 15, 25, 30],
            ['Toothpaste Sachet', 'Toiletries', 'sachet', 8, 12, 45],
            ['e-Load 50 Wallet', 'Service', 'pc', 46, 50, 20],
            ['Marlboro pack', 'Vice', 'pack', 95, 110, 0],
            ['San Miguel Pale Pilsen', 'Vice', 'bottle', 78, 95, 0],
        ];

        $dead = [
            ['Garlic Peanuts', 'Pantry', 'pcs', 30, 45],
            ['Imported Chocolates', 'Snacks', 'pack', 120, 160],
            ['Sardines Blue Caldereta', 'Pantry', 'pack', 18, 24],
        ];

        foreach ($catalog as [$name, $category, $unit, $cost, $price, $qty]) {
            $business->products()->create([
                'name' => $name, 'category' => $category, 'unit' => $unit,
                'quantity' => $qty, 'cost_price' => $cost, 'selling_price' => $price,
                'low_stock_threshold' => 5, 'branch_id' => $this->randomBranchId($business),
            ]);
        }

        $deadIds = [];
        foreach ($dead as $i => [$name, $category, $unit, $cost, $price]) {
            $deadIds[] = $business->products()->create([
                'name' => $name, 'category' => $category, 'unit' => $unit,
                'quantity' => 6 + $i, 'cost_price' => $cost, 'selling_price' => $price,
                'low_stock_threshold' => 5, 'branch_id' => $this->randomBranchId($business),
            ])->id;
        }
        $this->deadProductIds = $deadIds;
    }

    private function loadCustomers(Business $business): void
    {
        if ($business->customers()->exists()) {
            return;
        }

        $customers = [
            ['Aling Rosa', 'trusted'], ['Mang Tonyo', 'trusted'], ['Ate Liza', 'watch'],
            ['Kuya Jun', 'none'], ['Aling Maria', 'trusted'], ['Boyet', 'watch'],
            ['Tita Carmen', 'trusted'], ['Mang Pedro', 'none'], ['Ate Beth', 'trusted'],
            ['Kuya Ramon', 'none'], ['Aling Susan', 'watch'], ['Tolits', 'none'],
        ];

        foreach ($customers as [$name, $tag]) {
            $business->customers()->create([
                'name' => $name,
                'phone' => '09'.mt_rand(200000000, 999999999),
                'tag' => $tag,
            ]);
        }
    }

    private function loadSalesAndUtang(Business $business): void
    {
        if ($business->sales()->exists()) {
            return;
        }

        $products = $business->products()
            ->whereNotIn('id', $this->deadProductIds)
            ->where('quantity', '>', 0)
            ->get();
        $customers = $business->customers()->get();
        if ($products->isEmpty()) {
            return;
        }

        $this->seedOpeningUtang($business, $customers);

        foreach (range(1, 30) as $daysAgo) {
            $day = Period::today()->subDays($daysAgo - 1);

            foreach (range(1, mt_rand(2, 6)) as $saleIndex) {
                // Older days get fewer sales — sales trend upward toward today.
                $lines = mt_rand(1, 3);
                if ($daysAgo > 20 && $lines > 2) {
                    $lines = 2;
                }

                $items = [];
                $total = 0;
                $total_cost = 0;
                foreach ($products->random($lines) as $product) {
                    $qty = min($product->quantity, mt_rand(1, 2));
                    if ($qty < 1) {
                        continue;
                    }
                    $items[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $product->selling_price,
                        'unit_cost' => $product->cost_price,
                    ];
                    $total += $product->selling_price * $qty;
                    $total_cost += $product->cost_price * $qty;
                }
                if (! $items) {
                    continue;
                }

                $payment = $this->weightedPayment();
                $customer = $customers->random();
                $branchId = $this->randomBranchId($business);

                $sale = $business->sales()->create([
                    'branch_id' => $branchId,
                    'customer_id' => $payment === 'utang' ? $customer->id : null,
                    'customer_name' => $payment === 'utang' ? $customer->name : null,
                    'date_time' => $day->copy()->addHours(7)->addMinutes(mt_rand(0, 840)),
                    'payment_type' => $payment,
                    'total' => $total,
                    'total_cost' => $total_cost,
                    'status' => 'active',
                    'items' => $items,
                ]);

                foreach ($items as $line) {
                    $product = Product::find($line['product_id']);
                    $product->decrement('quantity', $line['quantity']);
                    $product->forceFill(['last_sold_at' => $sale->date_time])->save();
                    StockMovement::create([
                        'business_id' => $business->id,
                        'product_id' => $product->id,
                        'product_name' => $line['name'],
                        'branch_id' => $branchId,
                        'type' => 'sale',
                        'quantity_change' => -$line['quantity'],
                        'cost_price' => $line['unit_cost'],
                        'date' => $sale->date_time->format('Y-m-d'),
                    ]);
                }

                if ($payment === 'utang') {
                    UtangEntry::create([
                        'business_id' => $business->id,
                        'branch_id' => $branchId,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'sale_id' => $sale->id,
                        'type' => 'utang',
                        'amount' => $total,
                        'date' => $day->toDateString(),
                        'due_date' => $day->copy()->addDays($business->default_due_days)->toDateString(),
                        'recorded_by_id' => $business->user_id,
                    ]);
                }
            }
        }
    }

    private function seedOpeningUtang(Business $business, $customers): void
    {
        $owners = $customers->random(min(5, $customers->count()));
        foreach ($owners as $owner) {
            UtangEntry::create([
                'business_id' => $business->id,
                'branch_id' => $this->randomBranchId($business),
                'customer_id' => $owner->id,
                'customer_name' => $owner->name,
                'type' => 'utang',
                'amount' => mt_rand(80, 400),
                'date' => now()->subDays(mt_rand(2, 20))->toDateString(),
                'due_date' => now()->subDays(mt_rand(0, 3))->toDateString(),
                'note' => 'Opening balance',
                'recorded_by_id' => $business->user_id,
            ]);
        }
    }

    private function weightedPayment(): string
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 60) {
            return 'cash';
        }
        if ($roll <= 75) {
            return 'gcash';
        }
        if ($roll <= 85) {
            return 'maya';
        }

        return 'utang';
    }

    private function loadExpenses(Business $business): void
    {
        if ($business->expenses()->exists()) {
            return;
        }

        $rows = [
            [1200, 'Restock/Supplies', 'Weekly restock from the palengke'],
            [850, 'Utilities', 'Electric bill'],
            [200, 'Internet/Load', 'e-load for the store phone'],
            [3500, 'Rent', 'Monthly stall rent'],
            [150, 'Transport', 'Jeep fare to supplier'],
        ];

        foreach ($rows as $i => [$amount, $category, $note]) {
            $business->expenses()->create([
                'branch_id' => $this->randomBranchId($business),
                'amount' => $amount,
                'category' => $category,
                'date' => now()->subDays(mt_rand(1, 25))->toDateString(),
                'note' => $note,
            ]);
        }
    }

    private function loadChat(Business $business): void
    {
        if ($business->chatMessages()->exists()) {
            return;
        }

        $business->chatMessages()->createMany([
            ['role' => 'user', 'content' => 'Kumita ba ako ngayong linggo?'],
            ['role' => 'assistant', 'content' => 'Sagot ng libro ng bangko: may limang utang pa ang Aling Rosa na ₱185.00, halos lahat nagbayad noong Martes. Salamat po!'],
        ]);
    }
}
