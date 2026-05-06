<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ColorController;
use App\Http\Controllers\Api\ProductController;

Route::post('/color/create', [ColorController::class, 'store']);
Route::get('/color/list', [ColorController::class, 'index']);
Route::get('/color/view/{id}', [ColorController::class, 'show']);
Route::put('/color/update/{id}', [ColorController::class, 'update']);
Route::delete('/color/delete/{id}', [ColorController::class, 'destroy']);

Route::post('/product/create', [ProductController::class, 'store']);
Route::get('/product/list', [ProductController::class, 'index']);
Route::get('/product/view/{id}', [ProductController::class, 'show']);
Route::put('/product/update/{id}', [ProductController::class, 'update']);
Route::delete('/product/delete/{id}', [ProductController::class, 'destroy']);
Route::post('/product/filter-by-color', [ProductController::class, 'filterByColor']);
Route::post('/product/search', [ProductController::class, 'search']);