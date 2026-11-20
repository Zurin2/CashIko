<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Utang is a debt record, not a payment method — drop it from payment_methods (v1 correction). */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('payment_methods')->where('code', 'utang')->get(['id', 'qr_path']);

        foreach ($rows as $row) {
            if ($row->qr_path) {
                Storage::disk('private')->delete($row->qr_path);
            }
        }

        DB::table('payment_methods')->where('code', 'utang')->delete();
    }

    public function down(): void
    {
        // Re-seed utang rows for businesses missing them (QR images are not restorable).
        $businesses = DB::table('businesses')->get(['id']);

        foreach ($businesses as $business) {
            $exists = DB::table('payment_methods')->where('business_id', $business->id)->where('code', 'utang')->exists();

            if (! $exists) {
                DB::table('payment_methods')->insert([
                    'business_id' => $business->id,
                    'code' => 'utang',
                    'label' => 'Utang',
                    'is_active' => true,
                    'sort' => 99,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
