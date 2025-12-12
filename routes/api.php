<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CIAuthController;
use App\Http\Controllers\Api\CIDataController;
use App\Http\Controllers\Api\ProduksiController;

Route::post('request-token', [CIAuthController::class, 'requestToken']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('data-users', [CIDataController::class, 'users']);
    Route::get('monitor', [ProduksiController::class, 'monitor']);
});