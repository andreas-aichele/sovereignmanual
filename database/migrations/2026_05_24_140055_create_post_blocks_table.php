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
        Schema::create('post_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 5)->default('en');
            $table->string('type')->default('markdown');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('markdown')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['post_id', 'locale', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_blocks');
    }
};
