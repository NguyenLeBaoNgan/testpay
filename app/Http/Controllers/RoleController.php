<?php

namespace App\Http\Controllers;

use App\DTOs\RoleDTO;
// use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Role;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        // if (Auth::user()->hasRole('admin')) {
        //     $roles = $roles->where('name', '!=', 'super-admin');
        // }
        return response()->json($roles);
    }


    public function store(RoleDTO $roleDTO)
    {

        // $id = (string) Str::ulid();
        // Log::info('Generated ID: ' . $id);
        Log::info('Role DTO:', (array) $roleDTO);

        // try {

        $role = Role::create([
            // 'id' => $id,
            'name' => $roleDTO->name,
            'guard_name' => 'web'
        ]);

        Log::info('Creating Role with data:', [
            'id' => $role->id,
            'name' => $roleDTO->name,
            'guard_name' => 'web',

        ]);

        if (!empty($roleDTO->permissions)) {
            $permissions = Permission::whereIn('name', $roleDTO->permissions)->get();

            $existingPermissions = $permissions->pluck('name')->toArray();
            $missingPermissions = array_diff($roleDTO->permissions, $existingPermissions);


            if (!empty($missingPermissions)) {
                return response()->json([
                    'message' => 'Role created successfully, Some permissions do not exist: ' . implode(', ', $missingPermissions),
                    'role_id' => $role->id,
                ], Response::HTTP_BAD_REQUEST);
            }

            $role->syncPermissions($permissions);
        }


        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions'),
            'role_id' => $role->id
        ], Response::HTTP_CREATED);
        // } catch (RoleAlreadyExists $e) {

        //     return response()->json([
        //         'message' => "A role '{$roleDTO->name}' already exists"
        //     ], Response::HTTP_CONFLICT);
        // }
    }


    public function update(RoleDTO $roleDTO, $roleId)
    {
        $role = Role::findOrFail($roleId);
        Log::info('Updating Role', [
            'role_id' => $role->id,
            'name' => $roleDTO->name,
            // 'permissions' => $roleDTO->permissions
        ]);
        $role->update([
            'name' => $roleDTO->name
        ]);
        Log::info('Role after update name', $role->toArray());
        // if (!empty($roleDTO->permissions)) {
        //     $role->syncPermissions($roleDTO->permissions);
        //     Log::info('Permissions synced', $roleDTO->permissions);
        // }
        $role->load('permissions');
        Log::info('Role after updating and loading permissions', $role->toArray());
        return response()->json(['message' => 'Role Updated Successfully', 'data' => $role], 200);
    }

    public function destroy($roleId)
    {
        $role = Role::find($roleId);
        $role->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully',
        ], 200);
    }


    // public function assignRole(RoleDTO $roleDTO, $id)
    // {
    //     $user = User::findOrFail($roleDTO->userId);
    //     $user->assignRole($roleDTO->name);

    //     return response()->json(['message' => 'Role assigned successfully']);
    // }

    public function show($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        return response()->json($role);
    }


    public function givePermissionToRole(RoleDTO $roleDTO, $roleId)
    {
        $role = Role::findOrFail($roleId);
        if ($role->name !== $roleDTO->name) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided name does not match the role.',
            ], 400);
        }
        foreach ($roleDTO->permissions as $permission) {
            $role->givePermissionTo($permission);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions added to role',
            'data' => $role->load('permissions')
        ], 200);
    }
}
