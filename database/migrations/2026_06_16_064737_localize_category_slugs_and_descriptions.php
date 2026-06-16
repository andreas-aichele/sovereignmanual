<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories') || Schema::hasColumn('categories', 'key')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('key')->nullable()->after('id');
            $table->json('localized_slug')->nullable()->after('key');
            $table->json('description')->nullable()->after('name');
        });

        DB::table('categories')
            ->select(['id', 'slug', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $category): void {
                $key = (string) $category->slug;
                $name = json_decode((string) $category->name, true, 512, JSON_THROW_ON_ERROR);

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'key' => $key,
                        'localized_slug' => json_encode([
                            'en' => $key,
                            'de' => $key === 'self-custody' ? 'selbstverwahrung' : $key,
                        ], JSON_THROW_ON_ERROR),
                        'description' => json_encode([
                            'en' => '',
                            'de' => '',
                        ], JSON_THROW_ON_ERROR),
                        'name' => json_encode($name, JSON_THROW_ON_ERROR),
                    ]);
            });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique('categories_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->renameColumn('localized_slug', 'slug');
            $table->unique('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'key')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('legacy_slug')->nullable()->after('id');
        });

        DB::table('categories')
            ->select(['id', 'key'])
            ->orderBy('id')
            ->get()
            ->each(function (object $category): void {
                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['legacy_slug' => $category->key]);
            });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique('categories_key_unique');
            $table->dropColumn(['key', 'slug', 'description']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->renameColumn('legacy_slug', 'slug');
            $table->unique('slug');
        });
    }
};
