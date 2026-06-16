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
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'lang')) {
            return;
        }

        DB::statement("ALTER TABLE categories MODIFY lang ENUM('".implode("','", Language::values())."') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || ! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'lang')) {
            return;
        }

        DB::statement('ALTER TABLE categories MODIFY lang VARCHAR(2) NOT NULL');
    }
};
