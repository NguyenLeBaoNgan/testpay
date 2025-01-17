<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
// use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SePayWebhookController;

Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
    // Route::get('/permissions', [PermissionController::class, 'index']);
    // Route::post('/permissions', [PermissionController::class, 'store']);
    // Route::put('/permissions/{id}', [PermissionController::class, 'update']);
    // Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    Route::apiResource('permissions', PermissionController::class);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
// Route::middleware('auth:sanctum')->get('/user/{id}', [UserController::class, 'show']);
// Route::post('/login', [LoginController::class, 'login']);


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/', [ProductController::class, 'index']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::put('/users', [UserController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
    Route::get('/getalluser', [UserController::class, 'getalluser']);
    Route::post('/users/{id}/role', [UserController::class, 'updateUserRole']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
    Route::post('/updateuser/{id}', [UserController::class, 'updateuser']);
});
Route::middleware(['auth:sanctum', 'role:super-admin'])->put('/user/{id}/permissions', [PermissionController::class, 'update']);

//ts
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/search/{name}', [CategoryController::class, 'searchCategory']);

    Route::apiResource('products', ProductController::class);
    Route::post('/products/search', [ProductController::class, 'searchProduct']);
    // Route::get('products', [ProductController::class, 'index']);
    // Route::post('products', [ProductController::class, 'store']);
    // Route::get('products/{id}', [ProductController::class, 'show']);
    Route::post('products/{id}', [ProductController::class, 'update']);
    // Route::delete('products/{id}', [ProductController::class, 'destroy']);


});
Route::post('/payments', [PaymentController::class, 'store'])->middleware('auth:sanctum');
Route::apiResource('products', ProductController::class);
Route::apiResource('categories', CategoryController::class);
Route::post('/check-stock', [OrderController::class, 'checkStock']);
//role
Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
    Route::apiResource('roles', RoleController::class);
    // Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles/{id}', [RoleController::class, 'givePermissionToRole']);
    // Route::put('roles/{id}', [RoleController::class, 'update']);
    // Route::delete('roles/{id}', [RoleController::class, 'destroy']);
});
// Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
//     Route::get('/history', [OrderController::class, 'getOrderHistory']);
// });
Route::middleware(['auth:sanctum', 'role:super-admin|admin'])->group(function () {
    //     Route::post('orders', [OrderController::class, 'store']);
    // Route::put('orders/{orderId}', [OrderController::class, 'update']);
    // Route::get('orders/{orderId}', [OrderController::class, 'show']);
    // Route::delete('orders/{orderId}', [OrderController::class, 'destroy']);
    Route::apiResource('orders', OrderController::class);
});
Route::apiResource('orders', OrderController::class)->middleware('auth:sanctum');
Route::group([
    'prefix' => '/sepay',
    'as' => 'sepay.',
    // 'middleware' => ['api'],
], function () {
    Route::post('/webhook', [SePayWebhookController::class, 'webhook'])->name('webhook');
});

Route::get('/history', [OrderController::class, 'getOrderHistory'])->middleware('auth:sanctum');
