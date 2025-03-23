<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ProcessController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Batch Management Routes
Route::prefix('batches')->name('api.batches.')->group(function () {
    Route::get('/next-number', [BatchController::class, 'getNextBatchNumber'])->name('next-number');
    Route::get('/', [BatchController::class, 'index'])->name('index');
    Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
    Route::post('/', [BatchController::class, 'store'])->name('store');
    Route::put('/{batch}', [BatchController::class, 'update'])->name('update');
    Route::delete('/{batch}', [BatchController::class, 'destroy'])->name('destroy');
    Route::put('/{batch}/status', [BatchController::class, 'updateStatus'])->name('update-status');
});

// Process Management Routes
Route::prefix('processes')->name('api.processes.')->group(function () {
    Route::get('/', [ProcessController::class, 'index'])->name('index');
    Route::get('/{process}', [ProcessController::class, 'show'])->name('show');
    Route::post('/', [ProcessController::class, 'store'])->name('store');
    Route::put('/{process}', [ProcessController::class, 'update'])->name('update');
    Route::delete('/{process}', [ProcessController::class, 'destroy'])->name('destroy');
}); 