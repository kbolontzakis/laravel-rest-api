<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
//     return $request->user();
// });

Route::controller(ProductController::class)->group(function () {
    Route::get('products', 'index');
    Route::get('products/{product}', 'show');
    Route::post('products', 'store');
    Route::put('products/{product}', 'update');
});

Route::controller(TagController::class)->group(function () {
    Route::get('tags', 'index');
});
