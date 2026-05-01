<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    private const GUARD = 'web';

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
        'tickets.close_own',
        'tickets.add_watcher',
        'tickets.manage_targets',
        'issue_tracking.manage',
        'departments.manage',
        'api_tokens.manage',
        'webhooks.manage',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(RoleName::ProviderAdmin->value, self::GUARD)->syncPermissions(self::PERMISSIONS);

        Role::findOrCreate(RoleName::Agent->value, self::GUARD)->syncPermissions([
            'tickets.view_any',
            'tickets.create',
            'tickets.update',
            'tickets.assign',
            'tickets.change_status',
            'tickets.comment_public',
            'tickets.comment_internal',
            'tickets.manage_priority',
            'tickets.add_watcher',
            'tickets.manage_targets',
        ]);

        Role::findOrCreate(RoleName::CustomerAdmin->value, self::GUARD)->syncPermissions([
            'users.manage',
            'users.invite',
            'tickets.view_company',
            'tickets.create',
            'tickets.comment_public',
            'tickets.close_own',
            'tickets.add_watcher',
            'api_tokens.manage',
            'webhooks.manage',
        ]);

        Role::findOrCreate(RoleName::CustomerUser->value, self::GUARD)->syncPermissions([
            'tickets.view_company',
            'tickets.create',
            'tickets.comment_public',
            'tickets.close_own',
            'tickets.add_watcher',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
