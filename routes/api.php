<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PrescribedRoutineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRoutineController;
use App\Http\Controllers\SeedController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserProductController;
use Illuminate\Support\Facades\Route;

/*
|-------------------------------------------------------------------------- API Routes
|--------------------------------------------------------------------------*/

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1');
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1');
Route::get('/products', [ProductController::class, 'index']);
Route::get('/team', [TeamController::class, 'index']);
Route::get('/news', [NewsController::class, 'index']);
Route::get('/routines/product/{productId}', [ProductRoutineController::class, 'byProduct']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);

    Route::get('/prescribed', [PrescribedRoutineController::class, 'index']);
    Route::post('/prescribed/{routine}/advance', [PrescribedRoutineController::class, 'advance']);

    Route::get('/user-products', [UserProductController::class, 'index']);
    Route::post('/user-products', [UserProductController::class, 'store']);
    Route::put('/user-products/{userProduct}', [UserProductController::class, 'update']);
    Route::post('/user-products/{userProduct}/advance', [UserProductController::class, 'advance']);
    Route::delete('/user-products/{userProduct}', [UserProductController::class, 'destroy']);
});

// Admin-only routes, authenticated against the admins table.
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/contact-messages', [ContactMessageController::class, 'index']);
    Route::patch('/admin/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead']);
    Route::delete('/admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::get('/admin/orders', [OrderController::class, 'allOrders']);
    Route::post('/orders/{order}/advance', [OrderController::class, 'advance']);
    Route::get('/admin/stats', [AdminController::class, 'stats']);
    Route::get('/admin/prescribed', [PrescribedRoutineController::class, 'all']);
    Route::post('/prescribed', [PrescribedRoutineController::class, 'store']);
    Route::put('/prescribed/{routine}', [PrescribedRoutineController::class, 'update']);
    Route::post('/prescribed/{routine}/reset', [PrescribedRoutineController::class, 'reset']);
    Route::delete('/prescribed/{routine}', [PrescribedRoutineController::class, 'destroy']);
    Route::get('/routines', [ProductRoutineController::class, 'index']);
    Route::post('/routines', [ProductRoutineController::class, 'store']);
    Route::put('/routines/{routine}', [ProductRoutineController::class, 'update']);
    Route::delete('/routines/{routine}', [ProductRoutineController::class, 'destroy']);
    Route::get('/admin/carts', [CartController::class, 'all']);
    Route::get('/admin/notifications', [NotificationController::class, 'index']);
    Route::get('/admin/notifications/unread', [NotificationController::class, 'unread']);
    Route::patch('/admin/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::patch('/admin/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/admin/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::get('/admin/team', [TeamController::class, 'all']);
    Route::post('/admin/team', [TeamController::class, 'store']);
    Route::put('/admin/team/{team}', [TeamController::class, 'update']);
    Route::delete('/admin/team/{team}', [TeamController::class, 'destroy']);

    Route::post('/admin/news', [NewsController::class, 'store']);
    Route::put('/admin/news/{post}', [NewsController::class, 'update']);
    Route::delete('/admin/news/{post}', [NewsController::class, 'destroy']);

    Route::post('/admin/seed', [SeedController::class, 'seed']);
});
