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
        Schema::create('content_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('bitcoin');
            $table->string('status')->index()->default('proposed');
            $table->unsignedTinyInteger('priority')->default(5);
            $table->string('audience_level')->default('beginner');
            $table->string('primary_language', 5)->default('en');
            $table->json('target_languages')->nullable();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('last_generated_at')->nullable();
            $table->text('brief')->nullable();
            $table->json('constraints')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_topics');
    }
};
