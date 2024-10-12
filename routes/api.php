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
Route::post('/login', [LoginController::class, 'login']);
