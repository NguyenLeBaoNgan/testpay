<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\DTOs\UserDTO;
use App\DTOs\RoleDTO;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function index()
    {
        $users = User::get();
        return response()->json($users);
    }

    public function store(UserDTO $userDTO)
    {
        $user = User::create([
            'name' => $userDTO->name,
            'email' => $userDTO->email,
            'password' => Hash::make($userDTO->password),
        ]);

        $user->assignRole($userDTO->roles);

        return response()->json($user, 201);
    }


    public function update(UserDTO $userDTO,  $id)
    {
        $user = User::findOrFail($id);
        Log::info('Updating user: ', ['id' => $id, 'request_data' => $userDTO->all()]);


        if ($userDTO->name) {
            $user->name = $userDTO->name;
        }
        if ($userDTO->email) {
            $user->email = $userDTO->email;
        }

        if ($userDTO->password) {
            $user->password = Hash::make($userDTO->password);
        }

        if ($userDTO->roles) {
            $user->syncRoles($userDTO->roles);
        }

        $user->save();
        Log::info('User updated successfully: ', $user->toArray());
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        Log::info('Attempting to delete user: ', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            // Thêm các thông tin khác nếu cần
        ]);

        if (Auth::user()->hasRole('admin|super-admin')) {
            $user->delete();
            Log::info('User deleted successfully: ', ['id' => $id]);

            return response()->json(['message' => 'User deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function show($id)
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        // $userDTO = new UserDTO(
        //     $user->id,
        //     $user->name,
        //     $user->email,
        //     $user->getRoleNames()->toArray(),
        //     $user->getAllPermissions()->toArray()
        // );
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles,
            'permissions' => $permissions,
        ]);
    }

    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role_name' => 'required|string|exists:roles,name',
        ]);

        $roleAssignmentDTO = new RoleDTO(
            $id,
            $request->role_name
        );

        $user = User::findOrFail($roleAssignmentDTO->userId);
        $user->assignRole($roleAssignmentDTO->roleName);

        return response()->json(['message' => 'Role assigned successfully']);
    }
    public function register(UserDTO $userDTO)
    {
        Log::info('Registering user: ', (array) $userDTO);
        $user = User::create([
            'name' => $userDTO->name,
            'email' => $userDTO->email,
            'password' => Hash::make($userDTO->password),
        ]);
        Log::info('User ID: ' . $user->id);

        $user->assignRole('admin');

        $permissions = $user->getAllPermissions();
        $permissionNames = $permissions->pluck('name')->toArray();
        return response()->json(new UserDTO($user->id, $user->name, $user->email, roles: ['admin'], permissions: [$permissionNames]), 201);
    }


    public function login(UserDTO $userDTO)
    {
        Log::info('Attempting login for email: ', ['email' => $userDTO->email]);
        if (!Auth::attempt(['email' => $userDTO->email, 'password' => $userDTO->password])) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $userDTO->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'message' => 'Logged in successfully',
        ], 200);
    }



    public function logout(Request $request)
    {
        // Auth::logout();
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }


    public function updateUserRole(Request $request, $id)
    {

        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);


        $user = User::findOrFail($id);


        if (Auth::user()->hasRole(['admin', 'superadmin'])) {

            $user->syncRoles([$request->role]);

            return response()->json(['message' => 'User role updated successfully.']);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }


    //test

}
