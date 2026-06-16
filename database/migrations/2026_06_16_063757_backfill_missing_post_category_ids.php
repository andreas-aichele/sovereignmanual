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
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('posts', 'category_id')) {
            return;
        }

        if (Schema::hasColumn('categories', 'lang')) {
            DB::table('categories')->insertOrIgnore([
                'key' => 'self-custody',
                'lang' => Language::English->value,
                'slug' => 'self-custody',
                'name' => 'Self Custody',
                'description' => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('categories')->insertOrIgnore([
                'key' => 'self-custody',
                'slug' => json_encode([Language::English->value => 'self-custody', Language::German->value => 'selbstverwahrung'], JSON_THROW_ON_ERROR),
                'name' => json_encode([Language::English->value => 'Self Custody', Language::German->value => 'Selbstverwahrung'], JSON_THROW_ON_ERROR),
                'description' => json_encode([
                    Language::English->value => 'Guides for holding your own keys, planning recovery, and reducing custody risk without depending on custodians.',
                    Language::German->value => 'Anleitungen für eigene Schlüssel, Wiederherstellungspläne und geringere Verwahrungsrisiken ohne Abhängigkeit von Verwahrern.',
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $selfCustodyQuery = DB::table('categories')->where('key', 'self-custody');

        if (Schema::hasColumn('categories', 'lang')) {
            $selfCustodyQuery->where('lang', Language::English->value);
        }

        $selfCustodyId = $selfCustodyQuery->value('id');

        DB::table('posts')
            ->whereNull('category_id')
            ->update(['category_id' => $selfCustodyId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
