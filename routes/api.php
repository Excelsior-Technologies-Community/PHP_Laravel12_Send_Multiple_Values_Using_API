<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes (Readable Style)
|--------------------------------------------------------------------------
*/

// -------------------- COLORS CRUD --------------------
// Create a new color
Route::post('/color/create', [ColorController::class, 'store']);

// List all colors
Route::get('/color/list', [ColorController::class, 'index']);

// View a single color
Route::get('/color/view/{id}', [ColorController::class, 'show']);

// Update a color
Route::post('/color/update/{id}', [ColorController::class, 'update']); // POST used for update

// Delete a color
Route::post('/color/delete/{id}', [ColorController::class, 'destroy']);


// -------------------- PRODUCTS CRUD --------------------
// Create a new product
Route::post('/product/create', [ProductController::class, 'store']);

// List all products
Route::get('/product/list', [ProductController::class, 'index']);

// View a single product
Route::get('/product/view/{id}', [ProductController::class, 'show']);

// Update a product
Route::post('/product/update/{id}', [ProductController::class, 'update']); // POST used for update

// Delete a product
Route::post('/product/delete/{id}', [ProductController::class, 'destroy']);

// Filter products by color
Route::post('/product/filter-by-color', [ProductController::class, 'filterByColor']);

// Search products by name
Route::post('/product/search', [ProductController::class, 'search']);
