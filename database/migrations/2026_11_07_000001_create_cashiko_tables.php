<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One Business per user. RLS is enforced in Laravel: every tenant query
        // filters by the authenticated user's business id.
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // free text; onboarding allows custom types
            $table->string('location')->nullable();
            $table->string('plan')->default('basic'); // basic | premium
            $table->date('trial_ends_at')->nullable();
            $table->integer('default_due_days')->default(7);
            $table->string('default_reminder_tone')->default('gentle'); // gentle | firm
            $table->boolean('auto_remind')->default(false);
            $table->integer('low_stock_threshold')->default(5);
            $table->string('language')->default('taglish'); // english | taglish
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('password');
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable(); // composed display string
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('pcs');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('photo_path')->nullable(); // private disk path
            $table->date('expiration_date')->nullable();
            $table->timestamp('last_sold_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('tag')->default('none'); // none | trusted | watch
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->dateTime('date_time');
            $table->string('payment_type'); // cash | gcash | maya | utang
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('status')->default('active'); // active | void
            $table->json('items'); // [{product_id, name, quantity, unit_price, unit_cost}]
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('utang_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // utang | payment
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('category');
            $table->string('custom_category')->nullable();
            $table->date('date');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // restock | sale | adjustment | void
            $table->decimal('quantity_change', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('reason')->nullable();
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->text('message');
            $table->string('tone')->default('gentle'); // gentle | firm
            $table->string('status')->default('draft'); // draft | sent | scheduled
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // user | assistant
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'chat_messages', 'reminders', 'stock_movements', 'expenses',
            'utang_entries', 'sales', 'customers', 'products', 'branches',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_superadmin'));
        Schema::dropIfExists('businesses');
    }
};
