<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CouponApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\InventoryAlertApiController;
use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\ReturnApiController;
use App\Http\Controllers\Api\ReviewApiController;
use App\Http\Controllers\Api\SupplierApiController;
use App\Http\Controllers\Api\TagApiController;
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

        // 供应商 API
        Route::get('suppliers/all', [SupplierApiController::class, 'all']);
        Route::post('suppliers/{supplier}/toggle-status', [SupplierApiController::class, 'toggleStatus']);
        Route::get('suppliers/{supplier}/product-count', [SupplierApiController::class, 'getProductCount']);
        Route::apiResource('suppliers', SupplierApiController::class);

        // 商品 API
        Route::apiResource('products', ProductApiController::class);
        Route::get('products/{product}/inventory', [ProductApiController::class, 'show']);
        Route::post('products/batch/attach-tags', [ProductApiController::class, 'batchAttachTags']);
        Route::post('products/batch/detach-tags', [ProductApiController::class, 'batchDetachTags']);

        // 标签 API
        Route::get('tags/all', [TagApiController::class, 'all']);
        Route::apiResource('tags', TagApiController::class);

        // 订单 API
        Route::apiResource('orders', OrderApiController::class);
        Route::put('orders/{order}/status', [OrderApiController::class, 'updateStatus']);
        Route::get('orders/statistics', [OrderApiController::class, 'index']);

        // 库存 API
        Route::get('inventory', [InventoryApiController::class, 'index']);
        Route::get('inventory/statistics', [InventoryApiController::class, 'index']);
        Route::get('inventory/{product}', [InventoryApiController::class, 'show']);
        Route::put('inventory/{product}', [InventoryApiController::class, 'update']);

        // 库存预警 API
        Route::get('inventory-alerts', [InventoryAlertApiController::class, 'index']);
        Route::get('inventory-alerts/unread-count', [InventoryAlertApiController::class, 'unreadCount']);
        Route::post('inventory-alerts/scan', [InventoryAlertApiController::class, 'scan']);
        Route::post('inventory-alerts/mark-all-read', [InventoryAlertApiController::class, 'markAllAsRead']);
        Route::post('inventory-alerts/{id}/mark-read', [InventoryAlertApiController::class, 'markAsRead']);

        // 仪表盘 API
        Route::get('dashboard/summary', [DashboardApiController::class, 'summary']);
        Route::get('dashboard/charts', [DashboardApiController::class, 'charts']);

        // 优惠券 API
        Route::get('coupons/available', [CouponApiController::class, 'available']);
        Route::post('coupons/calculate', [CouponApiController::class, 'calculate']);
        Route::apiResource('coupons', CouponApiController::class);

        // 评价 API
        Route::get('reviews/statistics', [ReviewApiController::class, 'statistics']);
        Route::get('reviews/products-summary', [ReviewApiController::class, 'productsSummary']);
        Route::get('reviews/products/{productId}', [ReviewApiController::class, 'productReviews']);
        Route::get('reviews/products/{productId}/summary', [ReviewApiController::class, 'productSummary']);
        Route::post('reviews/{review}/approve', [ReviewApiController::class, 'approve']);
        Route::post('reviews/{review}/reject', [ReviewApiController::class, 'reject']);
        Route::post('reviews/{review}/toggle-visibility', [ReviewApiController::class, 'toggleVisibility']);
        Route::apiResource('reviews', ReviewApiController::class);

        // 退换货 API
        Route::apiResource('returns', ReturnApiController::class);
        Route::post('returns/{return}/approve', [ReturnApiController::class, 'approve']);
        Route::post('returns/{return}/reject', [ReturnApiController::class, 'reject']);
        Route::post('returns/{return}/complete', [ReturnApiController::class, 'complete']);
    });
});
