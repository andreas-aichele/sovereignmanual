<?php

use App\Enums\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('posts')
            || ! Schema::hasTable('categories')
            || ! Schema::hasColumn('posts', 'category_id')
            || ! Schema::hasColumn('categories', 'key')
            || ! Schema::hasColumn('categories', 'lang')) {
            return;
        }

        $selfCustodyId = DB::table('categories')
            ->where('key', 'self-custody')
            ->where('lang', Language::English->value)
            ->value('id');

        if ($selfCustodyId === null) {
            return;
        }

        DB::table('posts')
            ->whereNull('category_id')
            ->update(['category_id' => $selfCustodyId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The original missing category assignments cannot be recovered safely.
    }
};
