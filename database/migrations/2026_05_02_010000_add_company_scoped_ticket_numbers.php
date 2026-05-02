<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_ticket_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('next_number')->default(100001);
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('ticket_number')->nullable()->after('public_id');
        });

        DB::table('tickets')
            ->select('company_id')
            ->distinct()
            ->orderBy('company_id')
            ->get()
            ->each(function (object $row): void {
                $next = 100001;

                DB::table('tickets')
                    ->where('company_id', $row->company_id)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get(['id'])
                    ->each(function (object $ticket) use (&$next): void {
                        DB::table('tickets')
                            ->where('id', $ticket->id)
                            ->update(['ticket_number' => $next++]);
                    });

                DB::table('company_ticket_counters')->insert([
                    'company_id' => $row->company_id,
                    'next_number' => $next,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->unique(['company_id', 'ticket_number'], 'tickets_company_ticket_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropUnique('tickets_company_ticket_number_unique');
            $table->dropColumn('ticket_number');
        });

        Schema::dropIfExists('company_ticket_counters');
    }
};
