<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('{productId}/{storeId}', [ChatController::class, 'index'])->name('index')->where(['productId' => '[0-9]+', 'storeId' => '[0-9]+']);
    Route::post('{productId}/{storeId}', [ChatController::class, 'sendMessage'])->name('send')->where(['productId' => '[0-9]+', 'storeId' => '[0-9]+']);
    Route::get('{productId}/{storeId}/{userId}', [ChatController::class, 'viewMessage'])->name('viewMessage')->where(['productId' => '[0-9]+', 'storeId' => '[0-9]+', 'userId' => '[0-9]+']);
    Route::delete('{messageId}', [ChatController::class, 'destroy'])->name('delete')->where('messageId', '[0-9]+');
    Route::get('show/{chat}', [ChatController::class, 'show'])->name('show');

});
