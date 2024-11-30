<?php

namespace App\Http\Controllers;

use App\DTOs\PermissionDTO;
use Illuminate\Http\Request;
// use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Permission;

class PermissionController extends Controller
{


    public function index()
    {
        $permissions = Permission::all();

        Log::info('Request received', ['user_id' => auth()->id()]);
        // dd($permissions);
        return response()->json([
            'success' => true,
            'data' => $permissions
        ], 200);

    }


    public function store(PermissionDTO $permissionDTO)
{

    $permission = new Permission([
        'name' => $permissionDTO->name,
        'guard_name' => 'web',
    ]);

    if (empty($permission->id)) {
        $permission->id = (string) Str::ulid();
    }

    Log::info('Generated ULID before saving:', ['id' => $permission->id]);
    $permission->save();

    // $permission->refresh();

    Log::info('Created permission: ', [
        'id' => $permission->id,
        'name' => $permission->name,
        'guard_name' => $permission->guard_name,
        'created_at' => $permission->created_at,
    ]);

    return response()->json($permission, 201);
}

    public function update(PermissionDTO $permissionDTO, Permission $permission)
    {
        // $permission = Permission::findOrFail($id);

        $permissionDTO->validate([
            'name' => $permissionDTO->name . '|max:255',
        ]);
        if (Permission::where('name', $permissionDTO->name)->where('id', '!=', $permission->id)->exists()) {
            return response()->json(['error' => 'Permission name already exists'], 400);
        }
        if ($permission->name === $permissionDTO->name) {
            return response()->json($permission); 
        }
        $permission->name = $permissionDTO->name;
        $permission->save();

        Log::info('Updated permission: ', ['permission' => $permission]);

        return response()->json($permission);
    }


    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();

            Log::info('Deleted permission: ', ['id' => $id]);

            return response()->json(['message' => 'Permission deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Error deleting permission: ', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Error deleting permission.'], 500);
        }
    }


    public function show($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }
}
