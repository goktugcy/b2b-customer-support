<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_saved_views', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('section', 32)->index();
            $table->string('name');
            $table->jsonb('filters')->default('{}');
            $table->jsonb('columns')->nullable();
            $table->jsonb('sort')->nullable();
            $table->boolean('is_shared')->default(false)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['company_id', 'section', 'is_shared']);
            $table->index(['user_id', 'section']);
        });

        Schema::create('knowledge_base_categories', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_base_categories')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('visibility', 32)->default('public')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('knowledge_base_category_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('visibility', 32)->default('public')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'status', 'published_at']);
        });

        Schema::create('canned_responses', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('scope', 32)->default('global')->index();
            $table->string('title');
            $table->string('shortcut')->nullable()->index();
            $table->longText('body');
            $table->jsonb('variables')->default('[]');
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();

            $table->index(['scope', 'status']);
        });

        Schema::create('ticket_comment_mentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_comment_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('mentioned_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_comment_id', 'mentioned_user_id'], 'ticket_comment_mentions_unique');
            $table->index(['mentioned_user_id', 'notified_at']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('merged_into_ticket_id')->nullable()->after('assigned_to_user_id')->constrained('tickets')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('merged_into_ticket_id');
            $table->foreignId('merged_by_user_id')->nullable()->after('merged_at')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('split_from_ticket_id')->nullable()->after('merged_by_user_id')->constrained('tickets')->cascadeOnUpdate()->nullOnDelete();
            $table->index(['merged_into_ticket_id', 'merged_at']);
        });

        Schema::create('ticket_csat_surveys', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('sent_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['ticket_id', 'requester_user_id'], 'ticket_csat_ticket_requester_unique');
            $table->index(['company_id', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_csat_surveys');

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['merged_into_ticket_id']);
            $table->dropForeign(['merged_by_user_id']);
            $table->dropForeign(['split_from_ticket_id']);
            $table->dropIndex(['merged_into_ticket_id', 'merged_at']);
            $table->dropColumn([
                'merged_into_ticket_id',
                'merged_at',
                'merged_by_user_id',
                'split_from_ticket_id',
            ]);
        });

        Schema::dropIfExists('ticket_comment_mentions');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_categories');
        Schema::dropIfExists('ticket_saved_views');
        Schema::dropIfExists('notifications');
    }
};
