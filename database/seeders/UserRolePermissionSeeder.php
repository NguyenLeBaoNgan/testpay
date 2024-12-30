<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view role',
            'create role',
            'update role',
            'delete role',
            'view permission',
            'create permission',
            'update permission',
            'delete permission',
            'view user',
            'create user',
            'update user',
            'delete user',
            'view product',
            'create product',
            'update product',
            'delete product'
        ];

        // Tạo quyền (permissions) trước
        foreach ($permissions as $permission) {
            $perm = Permission::create([
                'id' => (string) Str::ulid(),
                'name' => $permission,
                'guard_name' => 'web',
            ]);
            Log::info("Permission created: {$perm->id} - {$perm->name}");
        }


        // Tạo vai trò (roles)
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin'], [
            'id' => (string) Str::ulid(),
            'guard_name' => 'web'
        ]);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'id' => (string) Str::ulid(),
            'guard_name' => 'web'
        ]);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], [
            'id' => (string) Str::ulid(),
            'guard_name' => 'web'
        ]);
        $userRole = Role::firstOrCreate(['name' => 'user'], [
            'id' => (string) Str::ulid(),
            'guard_name' => 'web'
        ]);

        Log::info("Super Admin Role ID: {$superAdminRole->id}");
        Log::info("Admin Role ID: {$adminRole->id}");
        Log::info("Staff Role ID: {$staffRole->id}");
        Log::info("User Role ID: {$userRole->id}");

        // Gán quyền cho super-admin
        $superAdminRole->syncPermissions(Permission::all());
        Log::info("Permissions for Super Admin Role: ", Permission::all()->pluck('name')->toArray());

        // Gán quyền cho admin
        $adminPermissions = Permission::whereIn('name', [
            'create role',
            'view role',
            'update role',
            'create permission',
            'view permission',
            'create user',
            'view user',
            'update user',
            'delete user',
            'create product',
            'view product',
            'update product'
        ])->get();
        $adminRole->syncPermissions($adminPermissions);
        Log::info("Permissions for Admin Role: ", $adminPermissions->pluck('name')->toArray());
        // Gán quyền cho staff
        $staffPermissions = Permission::whereIn('name', [
            'view product',
            'create product',
            'update product',
            'view user',
            'create user',
            'update user'
        ])->get();
        $staffRole->syncPermissions($staffPermissions);

        // Gán quyền cho user
        $userPermissions = Permission::whereIn('name', [
            'view product',
            'view user',
            'update user'
        ])->get();
        $userRole->syncPermissions($userPermissions);

        // Tạo người dùng (users)
        $superAdminUser = User::firstOrCreate(['email' => 'superadmin@gmail.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('12345678')
        ]);
        $superAdminUser->assignRole($superAdminRole);

        $adminUser = User::firstOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Admin',
            'password' => Hash::make('12345678')
        ]);
        $adminUser->assignRole($adminRole);

        $staffUser = User::firstOrCreate(['email' => 'staff@gmail.com'], [
            'name' => 'Staff',
            'password' => Hash::make('12345678')
        ]);
        $staffUser->assignRole($staffRole);
    }
}
