<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('status', 32)->default('open');
            $table->string('priority', 32)->default('normal');
            $table->string('source', 32)->default('portal');
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_customer_activity_at')->nullable();
            $table->timestamp('last_agent_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('ticket_comments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('visibility', 32)->default('public')->index();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'ticket_id']);
        });

        Schema::create('ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('ticket_comments')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('checksum', 128)->nullable();
            $table->string('visibility', 32)->default('public')->index();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();

            $table->index(['company_id', 'ticket_id']);
        });

        Schema::create('ticket_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('event_type')->index();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();

            $table->index(['company_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
    }
};
