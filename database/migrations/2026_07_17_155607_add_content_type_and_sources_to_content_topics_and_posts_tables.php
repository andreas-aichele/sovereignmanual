<?php

use App\Enums\ContentType;
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
        Schema::table('content_topics', function (Blueprint $table): void {
            $table->string('content_type')
                ->default(ContentType::Guide->value)
                ->after('category_id')
                ->index();
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->string('content_type')
                ->default(ContentType::Guide->value)
                ->after('category_id')
                ->index();
            $table->json('sources')->nullable()->after('ai_metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['content_type']);
            $table->dropColumn(['content_type', 'sources']);
        });

        Schema::table('content_topics', function (Blueprint $table): void {
            $table->dropIndex(['content_type']);
            $table->dropColumn('content_type');
        });
    }
};
