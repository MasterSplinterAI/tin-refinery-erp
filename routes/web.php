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
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\CurrencyExchangeController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XeroAuthController;
use App\Http\Controllers\XeroController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

// Public routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inventory routes
    Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{inventoryItem}', [InventoryItemController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');

    // Batch routes
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
    Route::put('/batches/{batch}/status', [BatchController::class, 'updateStatus'])->name('batches.update-status');

    // Process routes
    Route::get('/processes', [ProcessController::class, 'index'])->name('processes.index');
    Route::post('/processes', [ProcessController::class, 'store'])->name('processes.store');
    Route::get('/processes/{process}', [ProcessController::class, 'show'])->name('processes.show');
    Route::put('/processes/{process}', [ProcessController::class, 'update'])->name('processes.update');
    Route::delete('/processes/{process}', [ProcessController::class, 'destroy'])->name('processes.destroy');

    // Exchange Rates
    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('exchange-rates.store');
    Route::post('/exchange-rates/convert', [ExchangeRateController::class, 'convert'])->name('exchange-rates.convert');

    // Currency Exchanges
    Route::get('/currency-exchanges', [CurrencyExchangeController::class, 'index'])->name('currency-exchanges.index');
    Route::post('/currency-exchanges', [CurrencyExchangeController::class, 'store'])->name('currency-exchanges.store');
    Route::post('/currency-exchanges/retry-syncs', [CurrencyExchangeController::class, 'retryFailedSyncs'])->name('currency-exchanges.retry-syncs');
    Route::post('/currency-exchanges/{id}/sync-with-xero', [CurrencyExchangeController::class, 'syncWithXero'])->name('currency-exchanges.sync-with-xero');
    Route::get('/currency-exchanges/xero-bank-accounts', [CurrencyExchangeController::class, 'getXeroBankAccounts'])->name('currency-exchanges.xero-bank-accounts');
    Route::post('/currency-exchanges/check-xero-connection', [CurrencyExchangeController::class, 'checkXeroConnection'])->name('currency-exchanges.check-xero-connection');

    // Xero Integration routes
    Route::get('/xero/connect', [XeroController::class, 'connect'])->name('xero.connect');
    Route::post('/xero/disconnect', [XeroController::class, 'disconnect'])->name('xero.disconnect');
    Route::get('/xero/test', [XeroController::class, 'testConnection'])->name('xero.test');
});

// Xero callback route - must be outside auth middleware
Route::get('/xero/callback', [XeroController::class, 'callback'])->name('xero.callback')->withoutMiddleware(['verify.csrf']);

// Add a simple test callback route
Route::get('/xero/test-callback', function (Request $request) {
    // Log all request parameters
    Log::info('Xero test callback received', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'query' => $request->all(),
        'headers' => $request->headers->all()
    ]);
    
    // Just display the data we received
    return response()->json([
        'success' => true,
        'message' => 'Callback received',
        'data' => $request->all()
    ]);
})->name('xero.test-callback')->withoutMiddleware(['verify.csrf']);

// Add a simple Xero callback route
Route::get('/xero/simple-callback', function (Request $request) {
    // Log the request
    Log::info('Xero simple callback received', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'query' => $request->all()
    ]);
    
    // Just capture the code and state
    $code = $request->input('code');
    $state = $request->input('state');
    
    // Store in session
    session(['xero_auth_code' => $code, 'xero_state' => $state]);
    
    // Redirect to a page on your site that can process this
    return redirect('/process-xero-auth');
})->name('xero.simple-callback')->withoutMiddleware(['verify.csrf']);

// Processing route for Xero auth
Route::get('/process-xero-auth', function (Request $request) {
    $code = session('xero_auth_code');
    
    if (!$code) {
        Log::error('No Xero authorization code found in session');
        return redirect()->route('dashboard')->with('error', 'No authorization code received from Xero');
    }
    
    try {
        // Get the XeroService
        $xeroService = app()->make('xero');
        
        // Process the code
        $xeroService->getAccessToken($code);
        
        Log::info('Successfully processed Xero authorization code');
        return redirect()->route('dashboard')->with('success', 'Successfully connected to Xero');
    } catch (\Exception $e) {
        Log::error('Failed to process Xero authorization', ['error' => $e->getMessage()]);
        return redirect()->route('dashboard')->with('error', 'Failed to connect to Xero: ' . $e->getMessage());
    }
})->middleware(['auth'])->name('process-xero-auth');

// Add a minimal Xero callback endpoint for testing
Route::get('/xero/minimal-callback', function (Request $request) {
    // Just write the code to a file
    $code = $request->input('code');
    $state = $request->input('state');
    
    // Get all request info
    $data = [
        'code' => $code,
        'state' => $state,
        'all_params' => $request->all(),
        'time' => now()->toDateTimeString(),
        'headers' => $request->headers->all(),
        'host' => $request->getHost(),
        'url' => $request->fullUrl()
    ];
    
    // Save to a file since session might be problematic
    file_put_contents(
        storage_path('xero_auth_code.txt'), 
        json_encode($data, JSON_PRETTY_PRINT)
    );
    
    // Return a simple response
    return "Xero authorization received. Please return to the application.";
})->withoutMiddleware(['web', 'verify.csrf'])->name('xero.minimal-callback');

// Add an iframe-based redirect handler for Xero
Route::get('/xero/iframe-callback', function (Request $request) {
    return response()->view('xero.iframe-redirect', [
        'code' => $request->input('code'),
        'state' => $request->input('state')
    ]);
})->name('xero.iframe-callback')->withoutMiddleware(['web', 'verify.csrf']);

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

// Database test route
Route::get('/test-db', function () {
    try {
        $result = DB::select('SELECT 1');
        return response()->json(['status' => 'Database connection successful', 'result' => $result]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'Database connection failed', 'error' => $e->getMessage()], 500);
    }
});

// Add a test route for ngrok debugging
Route::get('/ngrok-test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Ngrok connection successful',
        'server_time' => now()->toDateTimeString(),
        'request_info' => [
            'host' => request()->getHost(),
            'method' => request()->method(),
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip(),
            'secure' => request()->secure(),
            'headers' => request()->headers->all(),
        ]
    ]);
});

// Add a basic test route with no middleware
Route::get('/basic-test', function () {
    return "Hello from Laravel! - " . now()->toDateTimeString();
})->withoutMiddleware('web');

// Add a simple route to test Xero connection
Route::get('/xero-test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Xero test route is accessible',
        'time' => now()->toDateTimeString(),
        'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
    ]);
});

// Health check route
Route::get('/up', function() {
    return "OK";
})->middleware([]); // Empty middleware

// Error test route
Route::get('/error-test', function () {
    Log::error('Intentional test error from error-test route', [
        'time' => now()->toDateTimeString(),
        'host' => request()->getHost(),
        'ip' => request()->ip()
    ]);
    
    throw new \Exception('This is a test exception to check error handling');
    
    return "You should not see this";
})->middleware([]);

// Xero test routes
Route::get('/xero-test-start', function () {
    Log::info('Starting Xero test flow', [
        'session_id' => session()->getId(),
        'timestamp' => now()->toDateTimeString()
    ]);
    
    return redirect()->to('/xero-test-callback?code=test_code&state=test_state');
});

Route::get('/xero-test-callback', function (Request $request) {
    Log::info('Xero test callback received', [
        'code' => $request->get('code'),
        'state' => $request->get('state'),
        'session_id' => session()->getId(),
        'user_agent' => $request->userAgent()
    ]);
    
    return view('xero.iframe-redirect', [
        'code' => $request->get('code'),
        'state' => $request->get('state')
    ]);
});

// Simplified Xero OAuth routes for direct testing
Route::get('/xero-direct', function () {
    Log::info('Using direct Xero auth method', [
        'session_id' => session()->getId()
    ]);
    
    // Get the configuration 
    $clientId = config('xero.client_id');
    $redirectUri = url('/xero-direct-callback');
    $state = Str::random(40);
    
    // Store the state in the session
    session(['xero_state' => $state]);
    
    // Generate the authorization URL manually
    $authUrl = 'https://login.xero.com/identity/connect/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'openid profile email accounting.transactions offline_access',
        'state' => $state
    ]);
    
    Log::info('Generated direct Xero auth URL', [
        'url_length' => strlen($authUrl),
        'redirect_uri' => $redirectUri
    ]);
    
    return redirect($authUrl);
});

Route::get('/xero-direct-callback', function (Request $request) {
    Log::info('Direct Xero callback received', [
        'code' => $request->has('code') ? 'present (length: ' . strlen($request->get('code')) . ')' : 'missing',
        'state' => $request->get('state'),
        'expected_state' => session('xero_state'),
        'session_id' => session()->getId()
    ]);
    
    return view('xero.iframe-redirect', [
        'code' => $request->get('code'),
        'state' => $request->get('state')
    ]);
});

// Xero test page
Route::get('/xero-test', function () {
    return view('xero.test-page');
})->name('xero.test.page');

// Add a route to verify Xero connection and fetch data
Route::get('/xero/verify-connection', function(Request $request) {
    // Set JSON headers for proper content type handling
    $headers = [
        'Content-Type' => 'application/json',
        'X-Content-Type-Options' => 'nosniff'
    ];
    
    try {
        // Get the XeroService
        $xeroService = app()->make('xero');
        
        // Check if we have a token
        $hasToken = \Illuminate\Support\Facades\Cache::has('xero_access_token');
        
        // If we don't have a token, we're not connected
        if (!$hasToken) {
            return response()->json([
                'connected' => false,
                'message' => 'Not connected to Xero. No access token found in cache.',
                'token_status' => 'missing'
            ], 200, $headers);
        }
        
        try {
            // Try to get some data from Xero to verify the connection
            $currencies = $xeroService->getCurrencies();
            
            // If we got here, the connection is working
            return response()->json([
                'connected' => true,
                'message' => 'Successfully connected to Xero!',
                'data' => [
                    'currencies' => $currencies,
                    'token_status' => 'valid'
                ]
            ], 200, $headers);
        } catch (\Exception $e) {
            // If we got an exception, try to refresh the token and try again
            \Illuminate\Support\Facades\Log::warning('Xero connection test failed, trying token refresh', [
                'error' => $e->getMessage()
            ]);
            
            try {
                // Try to refresh the token
                $refreshed = $xeroService->refreshToken();
                
                // If we get here, we successfully refreshed, try the currencies again
                $currencies = $xeroService->getCurrencies();
                
                return response()->json([
                    'connected' => true,
                    'message' => 'Successfully connected to Xero! (Token refreshed)',
                    'data' => [
                        'currencies' => $currencies, 
                        'token_status' => 'refreshed'
                    ]
                ], 200, $headers);
            } catch (\Exception $refreshError) {
                // Refresh also failed, now we can report the error
                \Illuminate\Support\Facades\Log::error('Xero connection test failed after token refresh', [
                    'error' => $refreshError->getMessage(),
                    'trace' => $refreshError->getTraceAsString()
                ]);
                
                return response()->json([
                    'connected' => false,
                    'message' => 'Error testing Xero connection after token refresh: ' . $refreshError->getMessage(),
                    'error_type' => get_class($refreshError)
                ], 500, $headers);
            }
        }
    } catch (\Exception $e) {
        // If we got an exception, log it and return error details
        \Illuminate\Support\Facades\Log::error('Xero connection test failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'connected' => false,
            'message' => 'Error testing Xero connection: ' . $e->getMessage(),
            'error_type' => get_class($e)
        ], 500, $headers);
    }
})->name('xero.verify-connection');
