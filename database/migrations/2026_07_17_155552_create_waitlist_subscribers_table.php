<?php

use App\Enums\Language;
use App\Enums\WaitlistSubscriberStatus;
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
        Schema::create('waitlist_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->enum('locale', Language::values())->default(Language::fallback()->value);
            $table->string('status')->default(WaitlistSubscriberStatus::Pending->value)->index();
            $table->string('action_token', 64)->nullable()->unique();
            $table->timestamp('consented_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_subscribers');
    }
};
