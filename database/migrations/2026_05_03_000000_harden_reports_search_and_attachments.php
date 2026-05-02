<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite' && Schema::hasColumn('ticket_attachments', 'scan_status')) {
            try {
                Schema::table('ticket_attachments', function (Blueprint $table): void {
                    $table->dropIndex('ticket_attachments_scan_status_index');
                });
            } catch (Throwable) {
                //
            }
        }

        Schema::table('ticket_attachments', function (Blueprint $table): void {
            $columns = collect(['scan_status', 'scan_result', 'scanned_at'])
                ->filter(fn (string $column): bool => Schema::hasColumn('ticket_attachments', $column))
                ->values()
                ->all();

            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("CREATE INDEX IF NOT EXISTS tickets_search_idx ON tickets USING GIN (to_tsvector('simple', coalesce(subject, '') || ' ' || coalesce(description, '')))");
        DB::statement("CREATE INDEX IF NOT EXISTS ticket_comments_search_idx ON ticket_comments USING GIN (to_tsvector('simple', coalesce(body, '')))");
        DB::statement("CREATE INDEX IF NOT EXISTS ticket_tags_search_idx ON ticket_tags USING GIN (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(slug, '')))");
        DB::statement("CREATE INDEX IF NOT EXISTS companies_search_idx ON companies USING GIN (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(slug, '')))");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS companies_search_idx');
            DB::statement('DROP INDEX IF EXISTS ticket_tags_search_idx');
            DB::statement('DROP INDEX IF EXISTS ticket_comments_search_idx');
            DB::statement('DROP INDEX IF EXISTS tickets_search_idx');
        }

        Schema::table('ticket_attachments', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_attachments', 'scan_status')) {
                $table->string('scan_status', 32)->default('skipped')->index();
            }

            if (! Schema::hasColumn('ticket_attachments', 'scan_result')) {
                $table->text('scan_result')->nullable();
            }

            if (! Schema::hasColumn('ticket_attachments', 'scanned_at')) {
                $table->timestamp('scanned_at')->nullable();
            }
        });
    }
};
