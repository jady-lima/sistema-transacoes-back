<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\Admin\UserController;
use \App\Http\Controllers\AccountController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/info', [AuthController::class, 'info']);
    Route::post('/logout', [AuthController::class, 'logout']);

    //Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {       
        Route::get('/contas', [AccountController::class, 'listAll']);
    });

    //Cliente
    Route::middleware('role:cliente')->prefix('cliente')->group(function () {
        Route::resource('contas', AccountController::class)->only(['store']);
    });
});