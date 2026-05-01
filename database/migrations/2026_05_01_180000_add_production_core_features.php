<?php

use App\Enums\TicketPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_sla_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('priority', 32);
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();

            $table->unique(['company_id', 'priority']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('first_responded_at')->nullable()->after('due_at');
            $table->timestamp('sla_first_response_breached_at')->nullable()->after('first_responded_at')->index();
            $table->timestamp('sla_resolution_breached_at')->nullable()->after('sla_first_response_breached_at')->index();
            $table->jsonb('sla_policy_snapshot')->nullable()->after('sla_resolution_breached_at');
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id')->unique();
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id')->unique();
        });

        DB::table('webhook_endpoints')->whereNull('public_id')->eachById(function (object $endpoint): void {
            DB::table('webhook_endpoints')->where('id', $endpoint->id)->update(['public_id' => (string) Str::ulid()]);
        });

        DB::table('webhook_deliveries')->whereNull('public_id')->eachById(function (object $delivery): void {
            DB::table('webhook_deliveries')->where('id', $delivery->id)->update(['public_id' => (string) Str::ulid()]);
        });

        $defaults = config('support.sla.defaults');
        $now = now();

        DB::table('companies')->where('type', 'client')->orderBy('id')->each(function (object $company) use ($defaults, $now): void {
            foreach (TicketPriority::cases() as $priority) {
                $values = $defaults[$priority->value];
                DB::table('company_sla_policies')->insertOrIgnore([
                    'company_id' => $company->id,
                    'priority' => $priority->value,
                    'first_response_minutes' => $values['first_response_minutes'],
                    'resolution_minutes' => $values['resolution_minutes'],
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->dropColumn('public_id');
        });

        Schema::table('webhook_endpoints', function (Blueprint $table): void {
            $table->dropColumn('public_id');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'first_responded_at',
                'sla_first_response_breached_at',
                'sla_resolution_breached_at',
                'sla_policy_snapshot',
            ]);
        });

        Schema::dropIfExists('company_sla_policies');
    }
};
