<?php

use App\Enums\Language;
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
        Schema::create('pillars', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->enum('lang', Language::values());
            $table->string('slug');
            $table->string('name');
            $table->text('description');
            $table->timestamps();

            $table->unique(['key', 'lang']);
            $table->unique(['lang', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pillars');
    }
};
