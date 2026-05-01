<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_departments', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('support_department_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_department_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['support_department_id', 'user_id'], 'support_department_user_unique');
        });

        Schema::create('ticket_target_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('support_department_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'support_department_id'], 'ticket_target_departments_unique');
        });

        Schema::create('ticket_target_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id'], 'ticket_target_users_unique');
        });

        Schema::create('ticket_watchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('side', 32)->index();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id'], 'ticket_watchers_ticket_user_unique');
            $table->index(['user_id', 'side']);
        });

        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id')->unique();
        });

        DB::table('ticket_attachments')
            ->whereNull('public_id')
            ->eachById(function (object $attachment): void {
                DB::table('ticket_attachments')
                    ->where('id', $attachment->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $table->dropColumn('public_id');
        });

        Schema::dropIfExists('ticket_watchers');
        Schema::dropIfExists('ticket_target_users');
        Schema::dropIfExists('ticket_target_departments');
        Schema::dropIfExists('support_department_user');
        Schema::dropIfExists('support_departments');
    }
};
