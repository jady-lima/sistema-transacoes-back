<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\Admin\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/info', [AuthController::class, 'info']);
    Route::post('/logout', [AuthController::class, 'logout']);

    //Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/clientes',  function () {
            return response()->json([
                'message' => 'Acesso permitido para admin.',
            ]);
        });
    });


    //Cliente
    Route::middleware('role:cliente')->prefix('cliente')->group(function () {
        Route::get('/perfil',  function () {
            return response()->json([
                'message' => 'Acesso permitido para cliente.',
            ]);
        });
    });
});