<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // public function login(Request $request)
    // {

    //     $request->validate([
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ]);


    //     if (Auth::attempt($request->only('email', 'password'))) {

    //         $user = Auth::user();

    //         $token = $user->createToken('YourAppName')->plainTextToken;
    //         session(['api_token' => $token]);


    //         return response()->json([
    //             'success' => true,
    //             'token' => $token,
    //         ], 200);
    //     }


    //     throw ValidationException::withMessages([
    //         'email' => ['Thông tin đăng nhập không chính xác.'],
    //     ]);
    // }
}
