<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-method payment accounts + QR codes (v1 addition). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code'); // cash|gcash|maya|utang|custom-{n}
            $table->string('label');
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('qr_path')->nullable(); // private disk, like product photos
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
