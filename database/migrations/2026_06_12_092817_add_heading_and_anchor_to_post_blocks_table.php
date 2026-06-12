<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('post_blocks', function (Blueprint $table): void {
            $table->string('heading')->nullable()->after('sort_order');
            $table->string('anchor')->nullable()->after('heading');

            $table->index(['post_id', 'locale', 'anchor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_blocks', function (Blueprint $table): void {
            $table->dropIndex(['post_id', 'locale', 'anchor']);
            $table->dropColumn(['heading', 'anchor']);
        });
    }
};
