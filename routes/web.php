<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Mua\ProfileController as MuaProfileController;
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
use App\Http\Controllers\SearchController as SearchControllerAPI;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Customer\BookingPaymentController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\Mua\ReportController;

// Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search.mua');
Route::get('/mua/{id}', [MuaProfileController::class, 'public'])->name('mua.public');

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register/customer', [CustomerAuthController::class])->name('register.customer');
    Route::post('/login/customer', [CustomerAuthController::class])->name('login.customer');
    Route::post('/register/mua', [MuaAuthController::class])->name('register.mua');
    Route::post('/login/mua', [MuaAuthController::class])->name('login.mua');
});

// Me & Users
Route::middleware('auth:sanctum')->get('/me', [MeController::class, 'index']);
Route::middleware('auth:sanctum')->get('/users', [MeController::class, 'listUsers']);

// MUA Services & Portfolio
Route::middleware(['auth:sanctum', 'mua'])->prefix('mua')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::post('/portfolio', [PortfolioController::class, 'store']);
    Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);

    Route::get('/bookings', [MuaBookingController::class, 'index']);
    Route::put('/bookings/{id}/status', [MuaBookingController::class, 'updateStatus']);

    Route::get('/profile', [MuaProfileController::class, 'show']);
    Route::put('/profile', [MuaProfileController::class, 'update']);

    Route::get('/reports', [ReportController::class, 'index']);
});

// Customer Routes
Route::middleware(['auth:sanctum', 'customer'])->prefix('customer')->group(function () {
    Route::get('/bookings', [CustomerBookingController::class, 'index']);
    Route::post('/bookings', [CustomerBookingController::class, 'store']);

    Route::get('/recommendations', [RecommendationController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{mua_id}', [WishlistController::class, 'destroy']);

    Route::get('/profile', [CustomerProfileController::class, 'show']);
    Route::put('/profile', [CustomerProfileController::class, 'update']);

    Route::get('/booking/{id}/pay', [BookingPaymentController::class, 'pay']);
});

// Public Routes
Route::get('/mua/{id}/availability', [AvailabilityController::class, 'show']);
Route::get('/mua/{mua_id}/reviews', function ($mua_id) {
    return \App\Models\Review::whereHas('booking', function ($q) use ($mua_id) {
        $q->where('mua_id', $mua_id);
    })->latest()->get();
});

// Notification & Chat
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead']);

    Route::get('/chat/{booking_id}', [ChatController::class, 'index']);
    Route::post('/chat/{booking_id}', [ChatController::class, 'store']);
});

// Midtrans Callback
Route::post('/midtrans/callback', [MidtransCallbackController::class, 'handle']);
