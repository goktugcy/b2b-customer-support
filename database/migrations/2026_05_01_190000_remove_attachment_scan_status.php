<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ticket_attachments', 'scan_status')) {
            Schema::table('ticket_attachments', function (Blueprint $table): void {
                $table->dropColumn('scan_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ticket_attachments', 'scan_status')) {
            Schema::table('ticket_attachments', function (Blueprint $table): void {
                $table->string('scan_status', 32)->default('pending')->index();
            });
        }
    }
};
