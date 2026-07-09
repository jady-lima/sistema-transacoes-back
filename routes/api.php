<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\AccountController;
use \App\Http\Controllers\UserController;

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/info', [AuthController::class, 'info']);
    Route::post('/logout', [AuthController::class, 'logout']);

    //Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {   
        Route::get('/usuarios', [UserController::class, 'listAll']);    
        Route::get('/contas', [AccountController::class, 'listAll']);
    });

    //Cliente
    Route::middleware('role:cliente')->prefix('cliente')->group(function () {
        Route::post('/contas', [AccountController::class, 'store']);
        Route::post('/contas/transacao', [AccountController::class, 'newTransaction']);
        Route::get('/contas', [AccountController::class, 'listAllClientTransaction']);
    });
});