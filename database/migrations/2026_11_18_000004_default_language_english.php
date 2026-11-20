<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * English is now the default UI language; the old "taglish" value is retired
 * in favour of "filipino". Existing businesses move to the new default.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('businesses')->where('language', 'taglish')->update(['language' => 'english']);
    }

    public function down(): void
    {
        DB::table('businesses')->where('language', 'english')->update(['language' => 'taglish']);
    }
};
