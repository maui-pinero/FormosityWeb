<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ShopController;
use App\Http\Controllers\API\CartController;

// Public routes

Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'register'])->name('register');
Route::post('/forgot-password', [UserController::class, 'forgotPassword']);
Route::post('/reset-password/{token}', [UserController::class, 'resetPassword']);

Route::get('/products/featured', [ShopController::class, 'featuredProducts']);
Route::get('/products/latest', [ShopController::class, 'latestProducts']);
Route::get('/page/{id}', [ShopController::class, 'page']);
Route::post('/contact-email', [ShopController::class, 'sendContactEmail']);
Route::get('/shop', [ShopController::class, 'index']);
Route::get('/categories', [ShopController::class, 'allCategories']);
Route::get('/subcategories', [ShopController::class, 'allSubCategories']);
Route::get('/products', [ShopController::class, 'allProducts']);
Route::get('/category/{categoryId}', [ShopController::class, 'category']);
Route::get('/subcategory/{subCategoryId}', [ShopController::class, 'subcategory']);
Route::get('/product/{productId}', [ShopController::class, 'product']);
Route::post('/product/{productId}/rating', [ShopController::class, 'saveRating']);

// Protected routes

Route::middleware('auth:sanctum')->group(function () {

    // User Dashboard
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [UserController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/address', [UserController::class, 'updateAddress']);
    Route::put('/user/address/edit', [UserController::class, 'updateAddress']);
    Route::get('/user/orders', [UserController::class, 'orders']);
    Route::get('/user/orders/{id}', [UserController::class, 'orderDetail']);
    Route::put('/user/orders/{id}/cancel', [UserController::class, 'cancelOrder']);
    Route::get('/user/wishlist', [UserController::class, 'wishlist']);
    Route::post('/user/wishlist', [UserController::class, 'addToWishlist']);
    Route::delete('/user/wishlist/{id}', [UserController::class, 'removeProductFromWishlist']);
    Route::post('/user/change-password', [UserController::class, 'changePassword']);

    Route::post('/checkout', [CartController::class, 'checkout']);

});