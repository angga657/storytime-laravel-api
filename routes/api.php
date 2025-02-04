<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\GetDataController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Resources\UserResource;

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

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/health', function () {
    return response()->json([
        'message' => 'Uhuk, I am alive!',
        'status' => 'OK',
        'code' => 200,
    ], 200);
});




Route::middleware(['auth:sanctum', 'check.token.expiry'])->group(function () {
    Route::apiResource('/categories', CategoryController::class)->except(['index', 'show']);
    Route::apiResource('/books', BookController::class)->except(['index','show']);
    Route::apiResource('/bookmarks', BookmarkController::class);


    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/upload-image', [UserController::class, 'uploadImage']);
    Route::put('/edit-profile', [UserController::class, 'editProfile']);

    Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/books-users/{id}', [GetDataController::class, 'getBookByUser']);
});

Route::apiResource('/categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('/books', BookController::class)->only(['index','show']);
Route::get('/books-category', [GetDataController::class, 'getBookByCategory']);
