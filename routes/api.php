<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\SourceNewsController;
use App\Http\Controllers\NewsReactionController;
use App\Http\Controllers\UserRecomandationsController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CheckedUsersNewsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ManualNewsController;
use App\Http\Controllers\FirebaseController;


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
Route::get('/fetch-news', [SourceNewsController::class, 'fetchFromRss']);

//Reactions
Route::middleware('auth:sanctum')->post('/news/{id}/react', [NewsReactionController::class, 'react']);

//Get categories
Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);


//Save prefrencess
Route::middleware('auth:sanctum')->post('/user/preferences', [UserRecomandationsController::class, 'store']);

//view personal info
Route::middleware('auth:sanctum')->get('/user/profile', [\App\Http\Controllers\UserProfileController::class, 'show']);


//Edite personal info
Route::middleware('auth:sanctum')->put('/user/profile', [\App\Http\Controllers\UserProfileController::class, 'update']);

//search (filter)
Route::get('/search/category/{category}', [SearchController::class, 'filterByCategory']);

//search (Search engine)
Route::post('/search', [SearchController::class, 'search']);

//Ai
Route::middleware('auth:sanctum')->post('/check-news', [CheckedUsersNewsController::class, 'check']);


//Notifications

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications/fetch', [NotificationsController::class, 'fetchNew']);
    Route::post('/notifications/{id}/seen', [NotificationsController::class, 'markAsSeen']);
});


Route::post('/manual-news', [ManualNewsController::class, 'store']);


Route::post('/notifications/send', [NotificationsController::class, 'sendFirebaseNotification']);

//من اجل الحصول على توكن الجهاز تبع المستخدم
Route::middleware('auth:sanctum')->post('/update-firebase-token', [FirebaseController::class, 'updateFirebaseToken']);
