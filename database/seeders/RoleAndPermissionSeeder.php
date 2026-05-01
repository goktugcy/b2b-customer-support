<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'companies.manage',
        'users.manage',
        'users.invite',
        'tickets.view_any',
        'tickets.view_company',
        'tickets.create',
        'tickets.update',
        'tickets.assign',
        'tickets.change_status',
        'tickets.comment_public',
        'tickets.comment_internal',
        'tickets.manage_priority',
        'api_tokens.manage',
        'webhooks.manage',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate(RoleName::ProviderAdmin->value)->syncPermissions(self::PERMISSIONS);

        Role::findOrCreate(RoleName::Agent->value)->syncPermissions([
            'tickets.view_any',
            'tickets.create',
            'tickets.update',
            'tickets.assign',
            'tickets.change_status',
            'tickets.comment_public',
            'tickets.comment_internal',
            'tickets.manage_priority',
        ]);

        Role::findOrCreate(RoleName::CustomerAdmin->value)->syncPermissions([
            'users.invite',
            'tickets.view_company',
            'tickets.create',
            'tickets.comment_public',
            'api_tokens.manage',
            'webhooks.manage',
        ]);

        Role::findOrCreate(RoleName::CustomerUser->value)->syncPermissions([
            'tickets.view_company',
            'tickets.create',
            'tickets.comment_public',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
