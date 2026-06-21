<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {
    // 认证路由（不需要认证）
    Route::post('login', [AuthController::class, 'login']);
    
    // 需要认证的路由
    Route::middleware('auth:sanctum')->group(function () {
        // 用户信息
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // 商品 API
        Route::apiResource('products', ProductApiController::class);
        Route::get('products/{product}/inventory', [ProductApiController::class, 'show']);

        // 订单 API
        Route::apiResource('orders', OrderApiController::class);
        Route::put('orders/{order}/status', [OrderApiController::class, 'updateStatus']);
        Route::get('orders/statistics', [OrderApiController::class, 'index']);

        // 库存 API
        Route::get('inventory', [InventoryApiController::class, 'index']);
        Route::get('inventory/statistics', [InventoryApiController::class, 'index']);
        Route::get('inventory/{product}', [InventoryApiController::class, 'show']);
        Route::put('inventory/{product}', [InventoryApiController::class, 'update']);

        // 仪表盘 API
        Route::get('dashboard/summary', [DashboardApiController::class, 'summary']);
        Route::get('dashboard/charts', [DashboardApiController::class, 'charts']);
    });
});
