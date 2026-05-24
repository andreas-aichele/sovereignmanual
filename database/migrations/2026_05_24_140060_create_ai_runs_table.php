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
        Schema::create('ai_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->index()->default('pending');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->longText('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('metrics')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
