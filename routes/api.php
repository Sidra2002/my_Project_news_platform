<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\SourceNewsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

//Register
Route::post('/register', [AuthController::class, 'register']);

//Login
Route::post('/login', [AuthController::class, 'login']);

//AdminNews
Route::post('/admin-news', [AdminNewsController::class, 'store']);

//SourceNews
Route::get('/test-fetch-news', [SourceNewsController::class, 'fetchFromRss']);
