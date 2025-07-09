<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\MuaAuthController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\Mua\ServiceController;
use App\Http\Controllers\Mua\PortfolioController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Mua\BookingController as MuaBookingController;
use App\Http\Controllers\Customer\RecommendationController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Mua\ProfileController as MuaProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Customer\BookingPaymentController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\Mua\ReportController;


Route::middleware('auth:sanctum')->get('/me', [MeController::class, 'index']);
Route::middleware('auth:sanctum')->get('/users', [MeController::class, 'listUsers']);

Route::prefix('auth')->group(function () {
    // Customer
    Route::post('/register/customer', [CustomerAuthController::class, 'register']);
    Route::post('/login/customer', [CustomerAuthController::class, 'login']);

    // MUA
    Route::post('/register/mua', [MuaAuthController::class, 'register']);
    Route::post('/login/mua', [MuaAuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::post('/portfolio', [PortfolioController::class, 'store']);
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);
});

// Customer Booking
Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::get('/bookings', [CustomerBookingController::class, 'index']);
    Route::post('/bookings', [CustomerBookingController::class, 'store']);
});

// MUA Booking Management
Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/bookings', [MuaBookingController::class, 'index']);
    Route::put('/bookings/{id}/status', [MuaBookingController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::get('/recommendations', [RecommendationController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});

Route::get('/mua/{mua_id}/reviews', function ($mua_id) {
    return \App\Models\Review::whereHas('booking', function ($q) use ($mua_id) {
        $q->where('mua_id', $mua_id);
    })->latest()->get();
});

Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{mua_id}', [WishlistController::class, 'destroy']);
});

// Customer profile
Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::get('/profile', [CustomerProfileController::class, 'show']);
    Route::put('/profile', [CustomerProfileController::class, 'update']);
});

// MUA profile
Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/profile', [MuaProfileController::class, 'show']);
    Route::put('/profile', [MuaProfileController::class, 'update']);
    Route::post('/profile', [ProfileController::class, 'store']);
});


Route::get('/mua/{id}', [MuaProfileController::class, 'public']);
Route::get('/mua/search', [SearchController::class, 'index']);
Route::get('/mua/{id}/availability', [AvailabilityController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/{booking_id}', [ChatController::class, 'index']);
    Route::post('/chat/{booking_id}', [ChatController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::get('/booking/{id}/pay', [BookingPaymentController::class, 'pay']);
});

Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle']);

Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});