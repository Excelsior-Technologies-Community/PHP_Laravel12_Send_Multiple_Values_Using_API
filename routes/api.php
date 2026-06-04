<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// -------------------- COLORS CRUD --------------------
Route::post('/color/create', [ColorController::class, 'store']);
Route::get('/color/list', [ColorController::class, 'index']);
Route::get('/color/view/{id}', [ColorController::class, 'show']);
Route::put('/color/update/{id}', [ColorController::class, 'update']);
Route::delete('/color/delete/{id}', [ColorController::class, 'destroy']);

// -------------------- PRODUCTS CRUD --------------------
Route::post('/product/create', [ProductController::class, 'store']);
Route::get('/product/list', [ProductController::class, 'index']);
Route::get('/product/view/{id}', [ProductController::class, 'show']);
Route::put('/product/update/{id}', [ProductController::class, 'update']);
Route::delete('/product/delete/{id}', [ProductController::class, 'destroy']);

// -------------------- NEW FEATURES --------------------

// Product search
Route::post('/product/search', [ProductController::class, 'search']);

// Filter products by color
Route::post('/product/filter-by-color', [ProductController::class, 'filterByColor']);

// Filter products by price range
Route::post('/product/filter-by-price', [ProductController::class, 'filterByPrice']);

// Bulk import products
Route::post('/product/bulk-import', [ProductController::class, 'bulkImport']);

// Export products
Route::get('/product/export', [ProductController::class, 'export']);

// Color-wise product count
Route::get('/product/color-wise-count', [ProductController::class, 'colorWiseCount']);

// Product statistics
Route::get('/product/statistics', [ProductController::class, 'statistics']);

// Soft delete operations
Route::delete('/product/force-delete/{id}', [ProductController::class, 'forceDelete']);
Route::post('/product/restore/{id}', [ProductController::class, 'restore']);
Route::get('/product/trashed', [ProductController::class, 'trashed']);