<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ProcessController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CurrencyExchangeController;

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

// Add a route to process Xero authorization from client-side
Route::post('/xero/process-auth', function (Request $request) {
    try {
        // Get code and state from the request, handling both JSON and form requests
        $code = $request->input('code');
        $state = $request->input('state');
        
        Log::info('Processing Xero auth from client', [
            'code' => $code ? 'provided (length: ' . strlen($code) . ')' : 'missing',
            'state' => $state ? 'provided (length: ' . strlen($state) . ')' : 'missing',
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'session_id' => session()->getId(),
            'has_csrf' => $request->hasHeader('X-CSRF-TOKEN') ? 'yes' : 'no'
        ]);
        
        if (!$code) {
            Log::error('No Xero authorization code provided in API request');
            return response()->json(['error' => 'No authorization code provided'], 400);
        }
        
        // Handle test code specially
        if ($code === 'test_code') {
            Log::info('Processing test authorization code');
            
            // Save test data to storage
            file_put_contents(
                storage_path('xero_auth_processed.txt'), 
                json_encode([
                    'code' => 'test_code',
                    'state' => $state,
                    'time' => now()->toDateTimeString(),
                    'status' => 'test_success'
                ], JSON_PRETTY_PRINT)
            );
            
            return response()->json([
                'success' => true, 
                'message' => 'Test Xero connection successful',
                'test_mode' => true
            ]);
        }
        
        try {
            // Get the XeroService
            $xeroService = app()->make('xero');
            
            Log::info('About to process authorization code via XeroService');
            
            // Process the code
            $xeroService->getAccessToken($code);
            
            // Also save to the storage file for backup
            file_put_contents(
                storage_path('xero_auth_processed.txt'), 
                json_encode([
                    'code' => 'code_present_' . strlen($code),
                    'state' => $state ? 'state_present_' . strlen($state) : 'no_state',
                    'time' => now()->toDateTimeString(),
                    'status' => 'processed'
                ], JSON_PRETTY_PRINT)
            );
            
            Log::info('Successfully processed Xero authorization from client');
            
            // Return JSON for API request, or redirect for browser request
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Xero connection established']);
            } else {
                return redirect()->route('dashboard')->with('success', 'Successfully connected to Xero');
            }
        } catch (\Exception $e) {
            Log::error('Failed to process Xero authorization from client', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            } else {
                return redirect()->route('dashboard')->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
            }
        }
    } catch (\Throwable $t) {
        Log::error('Unexpected error in Xero auth endpoint', [
            'error' => $t->getMessage(),
            'class' => get_class($t),
            'trace' => $t->getTraceAsString()
        ]);
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['error' => 'An unexpected error occurred: ' . $t->getMessage()], 500);
        } else {
            return redirect()->route('dashboard')->with('error', 'An unexpected error occurred: ' . $t->getMessage());
        }
    }
});

// Add an endpoint to handle module copying
Route::post('/copy-module', function (Request $request) {
    $module = $request->input('module');
    $originalUrl = $request->input('originalUrl');
    
    Log::info('Module loading failed, might need to copy', [
        'module' => $module,
        'originalUrl' => $originalUrl
    ]);
    
    // Search for the module file in the build assets
    $sourcePattern = public_path('build/assets/' . $module . '-*.js');
    $files = glob($sourcePattern);
    
    if (count($files) > 0) {
        $source = $files[0];
        $destination = public_path($module . '.js');
        
        // Copy the file
        try {
            copy($source, $destination);
            Log::info('Copied module file', [
                'from' => $source,
                'to' => $destination
            ]);
            return response()->json(['success' => true, 'file' => basename($destination)]);
        } catch (\Exception $e) {
            Log::error('Failed to copy module file', [
                'error' => $e->getMessage()
            ]);
        }
    } else {
        Log::warning('Could not find source module file', [
            'pattern' => $sourcePattern
        ]);
    }
    
    return response()->json(['success' => false]);
});

// Xero API endpoints
Route::get('/processes', [ProcessController::class, 'index'])->name('api.processes.index');
Route::post('/processes', [ProcessController::class, 'store'])->name('api.processes.store');
Route::get('/processes/{process}', [ProcessController::class, 'show'])->name('api.processes.show');
Route::put('/processes/{process}', [ProcessController::class, 'update'])->name('api.processes.update');
Route::delete('/processes/{process}', [ProcessController::class, 'destroy'])->name('api.processes.destroy');

// Currency Exchange routes
Route::get('/currency-exchanges', [CurrencyExchangeController::class, 'index'])->name('api.currency-exchanges.index');
Route::post('/currency-exchanges', [CurrencyExchangeController::class, 'store'])->name('api.currency-exchanges.store');
Route::post('/currency-exchanges/retry-syncs', [CurrencyExchangeController::class, 'retryFailedSyncs'])->name('api.currency-exchanges.retry-syncs');
Route::post('/currency-exchanges/{id}/sync-with-xero', [CurrencyExchangeController::class, 'syncWithXero'])->name('api.currency-exchanges.sync-with-xero');
Route::get('/currency-exchanges/xero-bank-accounts', [CurrencyExchangeController::class, 'getXeroBankAccounts'])->name('api.currency-exchanges.xero-bank-accounts'); 