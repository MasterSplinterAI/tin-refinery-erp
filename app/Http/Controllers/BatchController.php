<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BatchController extends Controller
{
    private BatchService $batchService;

    public function __construct(BatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    public function index()
    {
        Log::info('Fetching batches');
        $batches = Batch::with('processes')->get();
        
        if (request()->wantsJson()) {
            return response()->json($batches);
        }
        
        return Inertia::render('Batches', [
            'batches' => $batches
        ]);
    }

    public function create()
    {
        return Inertia::render('Batches/Create');
    }

    public function store(Request $request)
    {
        try {
            $this->batchService->validateBatchData($request->all());
            $batch = $this->batchService->createBatch($request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Batch created successfully',
                    'batch' => $batch
                ], 201);
            }

            return redirect()->route('batches.index');
        } catch (\Exception $e) {
            Log::error('Error in batch creation: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors([
                'general' => 'Error in batch creation: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function show($id)
    {
        // TODO: Implement batch details
        return Inertia::render('Batches/Show');
    }

    public function edit($id)
    {
        // TODO: Implement batch editing
        return Inertia::render('Batches/Edit');
    }

    public function update(Request $request, Batch $batch)
    {
        try {
            $this->batchService->validateBatchData($request->all());
            $updatedBatch = $this->batchService->updateBatch($batch, $request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Batch updated successfully',
                    'batch' => $updatedBatch
                ]);
            }

            return redirect()->route('batches.index');
        } catch (\Exception $e) {
            Log::error('Error in batch update: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors([
                'general' => 'Error in batch update: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function destroy(Batch $batch)
    {
        try {
            $this->batchService->deleteBatch($batch);

            if (request()->wantsJson()) {
                return response()->json(['message' => 'Batch deleted successfully']);
            }

            return redirect()->route('batches.index');
        } catch (\Exception $e) {
            Log::error('Error in batch deletion: ' . $e->getMessage());
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors([
                'general' => 'Error in batch deletion: ' . $e->getMessage()
            ]);
        }
    }

    public function updateStatus(Request $request, Batch $batch)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:in_progress,completed,cancelled'
            ]);

            $updatedBatch = $this->batchService->updateStatus($batch, $request->status);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Batch status updated successfully',
                    'batch' => $updatedBatch
                ]);
            }

            return redirect()->route('batches.index');
        } catch (\Exception $e) {
            Log::error('Error in batch status update: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors([
                'general' => 'Error in batch status update: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function getNextBatchNumber()
    {
        try {
            $nextNumber = $this->batchService->getNextBatchNumber();
            return response()->json(['nextNumber' => $nextNumber]);
        } catch (\Exception $e) {
            Log::error('Error getting next batch number: ' . $e->getMessage());
            return response()->json(['error' => 'Error getting next batch number'], 500);
        }
    }
} 