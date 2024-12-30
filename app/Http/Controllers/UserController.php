<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\DTOs\UserDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }
    public function getalluser()
    {
        return User::all();
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

    public function updateUserRole(UserDTO $userDTO, $id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->syncRoles($userDTO->roles);
        return response()->json($user);
    }
    public function update(UserDTO $userDTO)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!empty($userDTO->name)) {
            $user->name = $userDTO->name;
        }
        if (!empty($userDTO->email)) {
            $user->email = $userDTO->email;
        }

        if (!empty($userDTO->password)) {
            $user->password = Hash::make($userDTO->password);
        }

        // if (!empty($userDTO->roles)) {
        //     $user->syncRoles($userDTO->roles);
        // }

        $user->save();
        Log::info('User updated successfully: ', $user->toArray());
        return response()->json($user);
    }
    public function updateuser(UserDTO $userDTO, $id)
    {
        // $user = Auth::user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!empty($userDTO->name)) {
            $user->name = $userDTO->name;
        }
        if (!empty($userDTO->email)) {
            $user->email = $userDTO->email;
        }

        if (!empty($userDTO->password)) {
            $user->password = Hash::make($userDTO->password);
        }

        if (!empty($userDTO->roles)) {
            $user->syncRoles($userDTO->roles);
        }
        $user->save();

        return response()->json($user);
    }
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        Log::info('User roles:', Auth::user()->getRoleNames()->toArray());
        Log::info('Attempting to delete user: ', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
        if (Auth::id() == $id) {
            Log::warning('User attempted to delete themselves.', ['id' => $id]);
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }
        if (Auth::user()->hasAnyRole(['admin', 'super-admin'])) {
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
    public function register(UserDTO $userDTO)
    {
        Log::info('Registering user: ', (array) $userDTO);
        $user = User::create([
            'name' => $userDTO->name,
            'email' => $userDTO->email,
            'password' => Hash::make($userDTO->password),
        ]);
        Log::info('User ID: ' . $user->id);

        $user->assignRole('user');

        $permissions = $user->getAllPermissions();
        $permissionNames = $permissions->pluck('name')->toArray();
        return response()->json(new UserDTO($user->name, $user->email, $user->password, roles: ['user'], permissions: [$permissionNames]), 201);
    }


    public function login(UserDTO $userDTO)
    {
        Log::info('Attempting login for email: ', ['email' => $userDTO->email]);
        if (!Auth::attempt(['email' => $userDTO->email, 'password' => $userDTO->password])) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $userDTO->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        $roles = $user->roles->pluck('name')->toArray();
        return response()->json([
            'token' => $token,
            'role' => $roles,
            'message' => 'Logged in successfully',
        ], 200);
    }



    public function logout()
    {
        // Auth::logout();
        $user = Auth::user();
        if ($user) {
            Log::info('User logging out:', [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
            $user->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
