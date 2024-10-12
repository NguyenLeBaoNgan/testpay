<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
// Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
//     Route::get('/permissions', [PermissionController::class, 'index']);
// });
// Route::middleware(['auth:api'])->group(function () {
//             Route::get('/permissions', [PermissionController::class, 'index']);
//         });

// Route::group(['middleware' => ['role:super-admin|admin']], function() {


Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::middleware('auth:sanctum')->get('/user/{id}', [UserController::class, 'show']);
// Route::post('/login', [LoginController::class, 'login']);


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Route::middleware('auth:sanctum')->group(function () {
// Route::post('/logout', [UserController::class, 'logout']);
// });
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);

Route::middleware(['auth:sanctum', 'role:superadmin|admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
Route::middleware(['auth:sanctum', 'role:admin|superadmin'])->put('/user/{id}', [UserController::class, 'update']);
