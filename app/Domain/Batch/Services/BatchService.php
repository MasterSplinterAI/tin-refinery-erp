<?php

namespace App\Domain\Batch\Services;

use App\Domain\Batch\Models\Batch;
use App\Domain\Process\Models\Process;
use App\Domain\Process\Services\ProcessService;
use App\Domain\Inventory\Services\InventoryTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BatchService
{
    private InventoryTransactionService $inventoryService;
    private ProcessService $processService;

    public function __construct(
        InventoryTransactionService $inventoryService,
        ProcessService $processService
    ) {
        $this->inventoryService = $inventoryService;
        $this->processService = $processService;
    }

    public function createBatch(array $data): Batch
    {
        try {
            DB::beginTransaction();

            $batch = Batch::create([
                'batchNumber' => $data['batchNumber'],
                'date' => $data['date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null
            ]);

            if (isset($data['processes']) && is_array($data['processes'])) {
                foreach ($data['processes'] as $processData) {
                    $process = $this->processService->createProcess($batch, $processData);
                    
                    if ($batch->status === 'completed') {
                        $this->inventoryService->handleBatchTransactions($batch, $processData);
                    }
                }
            }

            DB::commit();
            Log::info('Batch created successfully', ['batch_id' => $batch->id]);

            return $batch->load('processes');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in batch creation: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateBatch(Batch $batch, array $data): Batch
    {
        try {
            DB::beginTransaction();

            // Add the batch ID to the data array for validation
            $data['id'] = $batch->id;
            
            // Debug logging
            Log::debug('Starting batch update', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batchNumber,
                'new_data' => $data
            ]);

            // Check if any other batch has this number
            $existingBatch = Batch::where('batchNumber', $data['batchNumber'])
                ->where('id', '!=', $batch->id)
                ->first();
            
            if ($existingBatch) {
                Log::error('Duplicate batch number found', [
                    'current_batch_id' => $batch->id,
                    'existing_batch_id' => $existingBatch->id,
                    'batch_number' => $data['batchNumber']
                ]);
                throw ValidationException::withMessages([
                    'batchNumber' => ['This batch number is already in use by another batch.']
                ]);
            }

            // If we get here, the batch number is unique or belongs to this batch
            $this->validateBatchData($data);

            // If the batch was previously completed, reverse the inventory transactions
            if ($batch->status === 'completed') {
                $this->inventoryService->reverseTransactions($batch);
            }

            $batch->update([
                'batchNumber' => $data['batchNumber'],
                'date' => $data['date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            if (isset($data['processes']) && is_array($data['processes'])) {
                // Delete existing processes
                $batch->processes()->delete();

                // Create new processes
                foreach ($data['processes'] as $processData) {
                    $process = $this->processService->createProcess($batch, $processData);
                    
                    if ($batch->status === 'completed') {
                        $this->inventoryService->handleBatchTransactions($batch, $processData);
                    }
                }
            }

            DB::commit();
            Log::info('Batch updated successfully', ['batch_id' => $batch->id]);

            return $batch->load('processes');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in batch update: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateStatus(Batch $batch, string $newStatus): Batch
    {
        try {
            DB::beginTransaction();

            // If the batch was previously completed, reverse the inventory transactions
            if ($batch->status === 'completed') {
                $this->inventoryService->reverseTransactions($batch);
            }

            $batch->update(['status' => $newStatus]);

            // If the batch is now completed, handle inventory transactions
            if ($newStatus === 'completed') {
                $batch->load('processes'); // Ensure processes are loaded
                foreach ($batch->processes as $process) {
                    $processData = $process->toArray();
                    // Only process if there are inventory items involved
                    if (!empty($processData['inputTinInventoryItemId']) || 
                        !empty($processData['outputTinInventoryItemId']) || 
                        !empty($processData['inputSlagInventoryItemId']) || 
                        !empty($processData['outputSlagInventoryItemId'])) {
                        $this->inventoryService->handleBatchTransactions($batch, $processData);
                    }
                }
            }

            DB::commit();
            Log::info('Batch status updated successfully', ['batch_id' => $batch->id, 'new_status' => $newStatus]);

            return $batch->load('processes');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in batch status update: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteBatch(Batch $batch): void
    {
        try {
            DB::beginTransaction();

            if ($batch->status === 'completed') {
                $this->inventoryService->reverseTransactions($batch);
            }

            $batch->processes()->delete();
            $batch->delete();

            DB::commit();
            Log::info('Batch deleted successfully', ['batch_id' => $batch->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in batch deletion: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getNextBatchNumber(): int
    {
        $date = now();
        $datePrefix = $date->format('dmy-');

        $todayBatches = Batch::where('batchNumber', 'like', $datePrefix . '%')
            ->get()
            ->map(function ($batch) use ($datePrefix) {
                return (int) substr($batch->batchNumber, strlen($datePrefix));
            })
            ->toArray();

        if (empty($todayBatches)) {
            return 1;
        }

        sort($todayBatches);
        $maxNumber = max($todayBatches);

        for ($i = 1; $i <= $maxNumber; $i++) {
            if (!in_array($i, $todayBatches)) {
                return $i;
            }
        }

        return $maxNumber + 1;
    }

    public function validateBatchData(array $data): bool
    {
        $rules = [
            'batchNumber' => 'required|string',
            'date' => 'required|date',
            'status' => 'required|string|in:in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'processes' => 'nullable|array',
            'processes.*.processNumber' => 'required_with:processes|integer',
            'processes.*.processingType' => 'required_with:processes|string|in:kaldo_furnace,refining_kettle',
            'processes.*.inputTinKilos' => 'nullable|numeric|min:0',
            'processes.*.inputTinSnContent' => 'nullable|numeric|min:0|max:100|decimal:0,4',
            'processes.*.inputTinInventoryItemId' => 'nullable|exists:inventory_items,id',
            'processes.*.outputTinKilos' => 'nullable|numeric|min:0',
            'processes.*.outputTinSnContent' => 'nullable|numeric|min:0|max:100|decimal:0,4',
            'processes.*.outputTinInventoryItemId' => 'nullable|exists:inventory_items,id',
            'processes.*.inputSlagKilos' => 'nullable|numeric|min:0',
            'processes.*.inputSlagSnContent' => 'nullable|numeric|min:0|max:100|decimal:0,4',
            'processes.*.inputSlagInventoryItemId' => 'nullable|exists:inventory_items,id',
            'processes.*.outputSlagKilos' => 'nullable|numeric|min:0',
            'processes.*.outputSlagSnContent' => 'nullable|numeric|min:0|max:100|decimal:0,4',
            'processes.*.outputSlagInventoryItemId' => 'nullable|exists:inventory_items,id',
            'processes.*.notes' => 'nullable|string'
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return true;
    }
} 