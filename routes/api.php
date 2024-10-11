<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

// Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
//     Route::get('/permissions', [PermissionController::class, 'index']);
// });
// Route::middleware(['auth:api'])->group(function () {
//             Route::get('/permissions', [PermissionController::class, 'index']);
//         });

// Route::group(['middleware' => ['role:super-admin|admin']], function() {


    Route::middleware(['api'])->group(callback: function () {
        Route::get('/permissions', action: [PermissionController::class, 'index']);
    });

    // Route::get('/permissions', [PermissionController::class, 'index']);
// });
