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
        if (! Schema::hasTable('categories') || Schema::hasColumn('categories', 'lang')) {
            return;
        }

        $localizedRows = [];

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('lang', 2)->nullable()->after('key');
            $table->string('slug_value')->nullable()->after('lang');
            $table->string('name_value')->nullable()->after('slug_value');
            $table->text('description_value')->nullable()->after('name_value');
        });

        DB::table('categories')
            ->select(['id', 'key', 'slug', 'name', 'description'])
            ->orderBy('id')
            ->get()
            ->each(function (object $category) use (&$localizedRows): void {
                $slugs = json_decode((string) $category->slug, true, 512, JSON_THROW_ON_ERROR);
                $names = json_decode((string) $category->name, true, 512, JSON_THROW_ON_ERROR);
                $descriptions = json_decode((string) $category->description, true, 512, JSON_THROW_ON_ERROR);

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'lang' => 'en',
                        'slug_value' => $slugs['en'] ?? $category->key,
                        'name_value' => $names['en'] ?? $category->key,
                        'description_value' => $descriptions['en'] ?? '',
                    ]);

                $localizedRows[] = [
                    'key' => $category->key,
                    'lang' => 'de',
                    'slug' => $slugs['de'] ?? $slugs['en'] ?? $category->key,
                    'name' => $names['de'] ?? $names['en'] ?? $category->key,
                    'description' => $descriptions['de'] ?? $descriptions['en'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropUnique('categories_key_unique');
            $table->dropColumn(['slug', 'name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->renameColumn('slug_value', 'slug');
            $table->renameColumn('name_value', 'name');
            $table->renameColumn('description_value', 'description');
        });

        DB::statement('ALTER TABLE categories MODIFY lang VARCHAR(2) NOT NULL');
        DB::statement('ALTER TABLE categories MODIFY slug VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE categories MODIFY name VARCHAR(255) NOT NULL');

        DB::table('categories')->insertOrIgnore($localizedRows);

        Schema::table('categories', function (Blueprint $table): void {
            $table->unique(['key', 'lang']);
            $table->unique(['lang', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
