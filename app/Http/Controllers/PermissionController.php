<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            header('X-Custom-Header: Some Value');
            return $next($request);
        });

        // $this->middleware('auth');
        // $this->middleware('permission:view permission', ['only' => ['index']]);
        // $this->middleware('permission:create permission', ['only' => ['create','store']]);
        // $this->middleware('permission:update permission', ['only' => ['update','edit']]);
        // $this->middleware('permission:delete permission', ['only' => ['destroy']]);
    }

    public function index()
    {
        $permissions = Permission::all();
        // return view('role-permission.permission.index', ['permissions' => $permissions]);
        // return response()->json($permissions);
        Log::info('Request received', ['user_id' => auth()->id()]);

        return response()->json([
            'success' => true,
            'data' => $permissions
        ], 200);
        // $permissions = Permission::all();
        // return response()->json(['permissions' => $permissions], 200);
        // return response()->json(['permissions' => ['view', 'edit', 'delete']]);

        // $permissions = Permission::get();
        // return view('role-permission.permission.index', ['permissions' => $permissions]);
    }

    // public function create()
    // {
    //     return view('role-permission.permission.create');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'unique:permissions,name'
            ]
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);
        Log::info('Created permission: ', ['permission' => $permission]);
        return response()->json($permission, 201);
    }

    // public function edit(Permission $permission)
    // {
    //     return view('role-permission.permission.edit', ['permission' => $permission]);
    // }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id . '|max:255',
        ]);

        $permission->name = $request->name;
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

}
