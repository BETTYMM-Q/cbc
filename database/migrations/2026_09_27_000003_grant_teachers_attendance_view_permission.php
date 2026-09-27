<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $permission = Permission::findOrCreate('view attendance', 'web');
        foreach (['teacher', 'pre-primary-teacher', 'lower-primary-teacher', 'upper-primary-teacher', 'junior-secondary-teacher'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permission);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::findByName('view attendance', 'web');
        foreach (['teacher', 'pre-primary-teacher', 'lower-primary-teacher', 'upper-primary-teacher', 'junior-secondary-teacher'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->revokePermissionTo($permission);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
