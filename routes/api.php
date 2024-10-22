<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
    // Route::get('/permissions', [PermissionController::class, 'index']);
    // Route::post('/permissions', [PermissionController::class, 'store']);
    // Route::put('/permissions/{id}', [PermissionController::class, 'update']);
    // Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    Route::apiResource('permissions', PermissionController::class);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::middleware('auth:sanctum')->get('/user/{id}', [UserController::class, 'show']);
// Route::post('/login', [LoginController::class, 'login']);


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/user/{id}', [UserController::class, 'update']);
    
});

Route::middleware(['auth:sanctum', 'role:admin|superadmin'])->group(function () {

    Route::post('/users/{id}/role', [UserController::class, 'updateUserRole']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
});
Route::middleware(['auth:sanctum', 'role:superadmin'])->put('/user/{id}/permissions', [PermissionController::class, 'update']);

//ts
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class);

    Route::apiResource('products', ProductController::class);
    // Route::get('products', [ProductController::class, 'index']);
    // Route::post('products', [ProductController::class, 'store']);
    // Route::get('products/{id}', [ProductController::class, 'show']);
    // Route::put('products/{id}', [ProductController::class, 'update']);
    // Route::delete('products/{id}', [ProductController::class, 'destroy']);

});
