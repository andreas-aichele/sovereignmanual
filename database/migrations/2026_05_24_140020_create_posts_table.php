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
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('status')->index()->default('draft');
            $table->string('topic');
            $table->string('audience_level')->default('beginner');
            $table->string('primary_language', 5)->default('en');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('next_review_at')->nullable()->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->decimal('review_score', 5, 2)->nullable();
            $table->json('review_summary')->nullable();
            $table->json('seo')->nullable();
            $table->json('ai_metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
