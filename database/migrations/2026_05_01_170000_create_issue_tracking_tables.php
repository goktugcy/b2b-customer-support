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
        Schema::create('support_projects', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('ticket_trackers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 32)->default('#2563eb');
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ticket_categories', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('support_project_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['support_project_id', 'slug']);
        });

        Schema::create('ticket_tags', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 32)->default('#64748b');
            $table->timestamps();
        });

        Schema::create('ticket_custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('ticket_tracker_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 32);
            $table->boolean('is_required')->default(false);
            $table->string('validation_regex')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->string('status', 32)->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['ticket_tracker_id', 'slug']);
        });

        Schema::create('ticket_custom_field_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_custom_field_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['ticket_custom_field_id', 'value']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('support_project_id')->nullable()->after('company_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ticket_tracker_id')->nullable()->after('support_project_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('ticket_category_id')->nullable()->after('ticket_tracker_id')->constrained()->cascadeOnUpdate()->nullOnDelete();

            $table->index(['support_project_id', 'status'], 'tickets_project_status_index');
            $table->index(['ticket_tracker_id', 'status'], 'tickets_tracker_status_index');
            $table->index(['ticket_category_id', 'status'], 'tickets_category_status_index');
        });

        Schema::create('ticket_tag', function (Blueprint $table): void {
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_tag_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['ticket_id', 'ticket_tag_id']);
        });

        Schema::create('ticket_custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ticket_custom_field_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'ticket_custom_field_id']);
        });

        $now = now();
        $trackerId = DB::table('ticket_trackers')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'name' => 'Support',
            'slug' => 'support',
            'description' => 'Default support issue tracker.',
            'color' => '#2563eb',
            'status' => 'active',
            'is_default' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $companyIds = DB::table('companies')
            ->where('type', 'client')
            ->pluck('id')
            ->merge(DB::table('tickets')->pluck('company_id'))
            ->unique()
            ->values();

        foreach ($companyIds as $companyId) {
            $projectId = DB::table('support_projects')->insertGetId([
                'public_id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'name' => 'General',
                'slug' => 'general',
                'description' => 'Default support project.',
                'status' => 'active',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('tickets')
                ->where('company_id', $companyId)
                ->whereNull('support_project_id')
                ->update([
                    'support_project_id' => $projectId,
                    'ticket_tracker_id' => $trackerId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_custom_field_values');
        Schema::dropIfExists('ticket_tag');

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropForeign(['support_project_id']);
            $table->dropForeign(['ticket_tracker_id']);
            $table->dropForeign(['ticket_category_id']);
            $table->dropIndex('tickets_project_status_index');
            $table->dropIndex('tickets_tracker_status_index');
            $table->dropIndex('tickets_category_status_index');
            $table->dropColumn(['support_project_id', 'ticket_tracker_id', 'ticket_category_id']);
        });

        Schema::dropIfExists('ticket_custom_field_options');
        Schema::dropIfExists('ticket_custom_fields');
        Schema::dropIfExists('ticket_tags');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('ticket_trackers');
        Schema::dropIfExists('support_projects');
    }
};
