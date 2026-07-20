<?php

use App\Enums\NewsletterSubscriberStatus;
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
        if (Schema::hasTable('waitlist_subscribers') && ! Schema::hasTable('newsletter_subscribers')) {
            Schema::rename('waitlist_subscribers', 'newsletter_subscribers');
        }

        if (! Schema::hasTable('newsletter_subscribers')) {
            return;
        }

        if (! Schema::hasColumn('newsletter_subscribers', 'action_token')) {
            Schema::table('newsletter_subscribers', function (Blueprint $table): void {
                $table->string('action_token', 64)->nullable()->unique()->after('status');
            });
        }

        DB::table('newsletter_subscribers')->update([
            'status' => NewsletterSubscriberStatus::Pending->value,
            'action_token' => null,
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('newsletter_subscribers') && ! Schema::hasTable('waitlist_subscribers')) {
            Schema::rename('newsletter_subscribers', 'waitlist_subscribers');
        }
    }
};
