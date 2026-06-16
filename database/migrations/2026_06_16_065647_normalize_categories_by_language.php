<?php

use App\Enums\Language;
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
            $table->enum('lang', Language::values())->nullable()->after('key');
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
                        'lang' => Language::English->value,
                        'slug_value' => $slugs[Language::English->value] ?? $category->key,
                        'name_value' => $names[Language::English->value] ?? $category->key,
                        'description_value' => $descriptions[Language::English->value] ?? '',
                    ]);

                $localizedRows[] = [
                    'key' => $category->key,
                    'lang' => Language::German->value,
                    'slug' => $slugs[Language::German->value] ?? $slugs[Language::English->value] ?? $category->key,
                    'name' => $names[Language::German->value] ?? $names[Language::English->value] ?? $category->key,
                    'description' => $descriptions[Language::German->value] ?? $descriptions[Language::English->value] ?? '',
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

        DB::statement("ALTER TABLE categories MODIFY lang ENUM('".implode("','", Language::values())."') NOT NULL");
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
