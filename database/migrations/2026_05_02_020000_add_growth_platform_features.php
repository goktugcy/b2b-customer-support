<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_article_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_base_article_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('editor_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('visibility', 32);
            $table->string('status', 32);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_base_article_id', 'version'], 'kb_article_versions_unique');
        });

        Schema::create('knowledge_base_article_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('knowledge_base_article_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('helpful')->index();
            $table->text('comment')->nullable();
            $table->string('ip_hash', 128)->nullable()->index();
            $table->timestamps();

            $table->index(['knowledge_base_article_id', 'helpful'], 'kb_article_feedback_article_helpful_idx');
        });

        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('format', 16)->index();
            $table->jsonb('filters')->default('{}');
            $table->string('status', 32)->default('pending')->index();
            $table->string('disk', 64)->default('local');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type', 'format']);
            $table->index(['requested_by_user_id', 'created_at']);
        });

        Schema::create('inbound_email_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('provider', 32)->index();
            $table->string('message_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->jsonb('raw_payload')->default('{}');
            $table->longText('parsed_body')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'message_id'], 'inbound_email_provider_message_unique');
            $table->index(['company_id', 'status']);
        });

        Schema::create('automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger', 64)->index();
            $table->jsonb('conditions')->default('{}');
            $table->jsonb('actions')->default('[]');
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'trigger', 'enabled']);
        });

        Schema::create('automation_rule_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_rule_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('trigger', 64)->index();
            $table->string('status', 32)->index();
            $table->jsonb('context')->default('{}');
            $table->jsonb('actions')->default('[]');
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->index();
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('database_enabled')->default(true);
            $table->boolean('mail_enabled')->default(true);
            $table->boolean('digest_enabled')->default(false);
            $table->jsonb('event_settings')->default('{}');
            $table->timestamps();
        });

        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->string('scan_status', 32)->default('skipped')->after('checksum')->index();
            $table->text('scan_result')->nullable()->after('scan_status');
            $table->timestamp('scanned_at')->nullable()->after('scan_result');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->dropColumn(['scan_status', 'scan_result', 'scanned_at']);
        });

        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('automation_rule_executions');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('inbound_email_messages');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('knowledge_base_article_feedback');
        Schema::dropIfExists('knowledge_base_article_versions');
    }
};
