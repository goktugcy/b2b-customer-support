<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id')->unique();
            $table->foreignId('company_id')->nullable()->after('public_id')->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('status', 32)->default('active')->after('password')->index();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['public_id', 'status', 'last_login_at']);
        });
    }
};
