<?php

use App\Http\Controllers\BookController;
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

// Route::middleware(['auth:sanctum', 'check.token.expiry'])->get('/user', function (Request $request) {
//     return $request->user();
// }); 

// Route::middleware(['auth:sanctum', 'check.token.expiry'])->get('/user', function (Request $request) {
//     return response()->json($request->user()->toArray(), 200);
// });

// Route::middleware(['auth:sanctum', 'check.token.expiry'])->get('/user', function (Request $request) {
//     return new UserResource($request->user());
// });

// Route::get('/user', function (Request $request) {
//     return new UserResource($request->user());
// });

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
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/books', BookController::class)->except(['index','show']);

    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/upload-image', [UserController::class, 'uploadImage']);
    Route::put('/edit-profile', [UserController::class, 'editProfile']);

    Route::get('/user', [UserController::class, 'getUser']);
});

Route::apiResource('/books', BookController::class)->only(['index', 'show']);
Route::get('/books-users', [BookController::class, 'userBooksIndex']);