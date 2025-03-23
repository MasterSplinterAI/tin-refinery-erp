<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryTransactionController;
use App\Models\Batch;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ProcessController;

// Include authentication routes
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Main application routes
    Route::get('/', function () {
        return redirect()->route('batches.index');
    });

    // Web routes for views
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
});

// API Routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    // Inventory routes
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('api.inventory.index');
    Route::post('/inventory', [InventoryItemController::class, 'store'])->name('api.inventory.store');
    Route::get('/inventory/{inventoryItem}', [InventoryItemController::class, 'show'])->name('api.inventory.show');
    Route::put('/inventory/{inventoryItem}', [InventoryItemController::class, 'update'])->name('api.inventory.update');
    Route::delete('/inventory/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('api.inventory.destroy');

    // Inventory transactions routes
    Route::get('/inventory-transactions', [InventoryTransactionController::class, 'index'])->name('api.inventory-transactions.index');
    Route::post('/inventory-transactions', [InventoryTransactionController::class, 'store'])->name('api.inventory-transactions.store');
    Route::get('/inventory-transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('api.inventory-transactions.show');
    Route::put('/inventory-transactions/{transaction}', [InventoryTransactionController::class, 'update'])->name('api.inventory-transactions.update');
    Route::delete('/inventory-transactions/{transaction}', [InventoryTransactionController::class, 'destroy'])->name('api.inventory-transactions.destroy');

    // Batch routes
    Route::get('/batches/next-number', [BatchController::class, 'getNextBatchNumber'])->name('api.batches.next-number');
    Route::get('/batches', [BatchController::class, 'index'])->name('api.batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('api.batches.store');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('api.batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('api.batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('api.batches.destroy');
    Route::put('/batches/{batch}/status', [BatchController::class, 'updateStatus'])->name('api.batches.update-status');

    // Process routes
    Route::get('/processes', [ProcessController::class, 'index'])->name('api.processes.index');
    Route::post('/processes', [ProcessController::class, 'store'])->name('api.processes.store');
    Route::get('/processes/{process}', [ProcessController::class, 'show'])->name('api.processes.show');
    Route::put('/processes/{process}', [ProcessController::class, 'update'])->name('api.processes.update');
    Route::delete('/processes/{process}', [ProcessController::class, 'destroy'])->name('api.processes.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory routes
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');
});

// Database test route
Route::get('/test-db', function () {
    try {
        $result = DB::select('SELECT 1');
        return response()->json(['status' => 'Database connection successful', 'result' => $result]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Database connection failed', 'error' => $e->getMessage()], 500);
    }
});
