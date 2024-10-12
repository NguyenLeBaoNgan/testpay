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
    public function __construct()
    {
        $this->middleware('permission:view user', ['only' => ['index']]);
        $this->middleware('permission:create user', ['only' => ['create','store']]);
        $this->middleware('permission:update user', ['only' => ['update','edit']]);
        $this->middleware('permission:delete user', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:20',
            'roles' => 'required'
        ]);

        $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->password),
                    ]);

                    $user->assignRole($request->roles);

                    return response()->json($user, 201);
    }


    public function update(Request $request,  $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|max:20',
            'roles' => 'required|array'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->syncRoles($request->roles);

        $user->save();

        return response()->json($user);
    }

    public function destroy($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        return response()->json(null, 204);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        $userDTO = new UserDTO(
            $user->id,
            $user->name,
            $user->email,
            $user->getRoleNames()->toArray(),
            $user->getAllPermissions()->toArray()
        );

        return response()->json($userDTO);
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
public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',

        ]);
        Log::info('Registering user: ', $request->all());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(new UserDTO($user->id, $user->name, $user->email, [], []), 201);
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
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
}
