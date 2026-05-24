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
        Schema::create('post_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('disk')->default('public');
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->text('prompt')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_assets');
    }
};
