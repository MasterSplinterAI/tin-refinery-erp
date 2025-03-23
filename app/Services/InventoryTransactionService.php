<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryTransactionService
{
    public function handleBatchTransactions(Batch $batch, array $processData): void
    {
        if ($batch->status !== 'completed') {
            Log::info("Skipping inventory transactions for batch {$batch->batchNumber} as status is not completed");
            return;
        }

        Log::info("Processing inventory transactions for batch {$batch->batchNumber}", [
            'batch_id' => $batch->id,
            'process_data' => $processData
        ]);

        try {
            DB::transaction(function () use ($batch, $processData) {
                // Validate inventory items exist before processing
                $this->validateInventoryItems($processData);
                
                $this->handleInputMaterials($batch, $processData);
                $this->handleOutputMaterials($batch, $processData);
                Log::info("Completed inventory transactions for batch {$batch->batchNumber}");
            });
        } catch (\Exception $e) {
            Log::error("Error processing inventory transactions for batch {$batch->batchNumber}", [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'process_data' => $processData
            ]);
            throw new \Exception("Error processing inventory transactions: " . $e->getMessage());
        }
    }

    public function reverseTransactions(Batch $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch->processes as $process) {
                $this->reverseInputMaterials($batch, $process->toArray());
                $this->reverseOutputMaterials($batch, $process->toArray());
            }
        });
    }

    private function handleInputMaterials(Batch $batch, array $processData): void
    {
        try {
            // Handle input tin
            if (!empty($processData['inputTinInventoryItemId']) && !empty($processData['inputTinKilos'])) {
                Log::info("Processing input tin", [
                    'batch_id' => $batch->id,
                    'item_id' => $processData['inputTinInventoryItemId'],
                    'quantity' => $processData['inputTinKilos']
                ]);
                $this->validateAndDeductMaterial(
                    $processData['inputTinInventoryItemId'],
                    $processData['inputTinKilos'],
                    $batch,
                    'input tin'
                );
            }

            // Handle input slag
            if (!empty($processData['inputSlagInventoryItemId']) && !empty($processData['inputSlagKilos'])) {
                Log::info("Processing input slag", [
                    'batch_id' => $batch->id,
                    'item_id' => $processData['inputSlagInventoryItemId'],
                    'quantity' => $processData['inputSlagKilos']
                ]);
                $this->validateAndDeductMaterial(
                    $processData['inputSlagInventoryItemId'],
                    $processData['inputSlagKilos'],
                    $batch,
                    'input slag'
                );
            }
        } catch (\Exception $e) {
            Log::error("Error processing input materials", [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleOutputMaterials(Batch $batch, array $processData): void
    {
        try {
            // Handle output tin
            if (!empty($processData['outputTinInventoryItemId']) && !empty($processData['outputTinKilos'])) {
                Log::info("Processing output tin", [
                    'batch_id' => $batch->id,
                    'item_id' => $processData['outputTinInventoryItemId'],
                    'quantity' => $processData['outputTinKilos']
                ]);
                $this->addMaterial(
                    $processData['outputTinInventoryItemId'],
                    $processData['outputTinKilos'],
                    $batch,
                    'output tin'
                );
            }

            // Handle output slag
            if (!empty($processData['outputSlagInventoryItemId']) && !empty($processData['outputSlagKilos'])) {
                Log::info("Processing output slag", [
                    'batch_id' => $batch->id,
                    'item_id' => $processData['outputSlagInventoryItemId'],
                    'quantity' => $processData['outputSlagKilos']
                ]);
                $this->addMaterial(
                    $processData['outputSlagInventoryItemId'],
                    $processData['outputSlagKilos'],
                    $batch,
                    'output slag'
                );
            }
        } catch (\Exception $e) {
            Log::error("Error processing output materials", [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function validateAndDeductMaterial(int $itemId, float $quantity, Batch $batch, string $type): void
    {
        $item = InventoryItem::findOrFail($itemId);
        
        if ($item->quantity < $quantity) {
            Log::error("Insufficient quantity for {$type} material", [
                'batch_id' => $batch->id,
                'item_id' => $item->id,
                'required' => $quantity,
                'available' => $item->quantity
            ]);
            throw new \Exception("Insufficient quantity for {$type} material");
        }

        InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'type' => 'consumption',
            'quantity' => -$quantity,
            'currency' => 'USD',
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'notes' => "Consumed in Batch {$batch->batchNumber}"
        ]);

        $item->decrement('quantity', $quantity);
        Log::info("Updated {$type} inventory quantity", [
            'item_id' => $item->id,
            'new_quantity' => $item->quantity
        ]);
    }

    private function addMaterial(int $itemId, float $quantity, Batch $batch, string $type): void
    {
        $item = InventoryItem::findOrFail($itemId);

        InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'type' => 'production',
            'quantity' => $quantity,
            'currency' => 'USD',
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'notes' => "Produced in Batch {$batch->batchNumber}"
        ]);

        $item->increment('quantity', $quantity);
        Log::info("Updated {$type} inventory quantity", [
            'item_id' => $item->id,
            'new_quantity' => $item->quantity
        ]);
    }

    private function reverseInputMaterials(Batch $batch, array $processData): void
    {
        if (!empty($processData['inputTinInventoryItemId'])) {
            $this->reverseMaterial(
                $processData['inputTinInventoryItemId'],
                $processData['inputTinKilos'],
                $batch,
                true
            );
        }

        if (!empty($processData['inputSlagInventoryItemId'])) {
            $this->reverseMaterial(
                $processData['inputSlagInventoryItemId'],
                $processData['inputSlagKilos'],
                $batch,
                true
            );
        }
    }

    private function reverseOutputMaterials(Batch $batch, array $processData): void
    {
        if (!empty($processData['outputTinInventoryItemId'])) {
            $this->reverseMaterial(
                $processData['outputTinInventoryItemId'],
                $processData['outputTinKilos'],
                $batch,
                false
            );
        }

        if (!empty($processData['outputSlagInventoryItemId'])) {
            $this->reverseMaterial(
                $processData['outputSlagInventoryItemId'],
                $processData['outputSlagKilos'],
                $batch,
                false
            );
        }
    }

    private function reverseMaterial(int $itemId, float $quantity, Batch $batch, bool $isInput): void
    {
        $item = InventoryItem::findOrFail($itemId);
        $type = $isInput ? 'consumption' : 'production';
        $reverseQuantity = $isInput ? $quantity : -$quantity;

        InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'type' => 'reversal',
            'quantity' => $reverseQuantity,
            'currency' => 'USD',
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'notes' => "Reversed {$type} from Batch {$batch->batchNumber}"
        ]);

        if ($isInput) {
            $item->increment('quantity', $quantity);
        } else {
            $item->decrement('quantity', $quantity);
        }
    }

    private function validateInventoryItems(array $processData): void
    {
        // Check input tin inventory item
        if (!empty($processData['inputTinInventoryItemId'])) {
            $item = InventoryItem::find($processData['inputTinInventoryItemId']);
            if (!$item) {
                throw new \Exception("Input tin inventory item not found");
            }
            Log::info("Validated input tin inventory item", [
                'item_id' => $item->id,
                'name' => $item->name,
                'current_quantity' => $item->quantity
            ]);
        }

        // Check output tin inventory item
        if (!empty($processData['outputTinInventoryItemId'])) {
            $item = InventoryItem::find($processData['outputTinInventoryItemId']);
            if (!$item) {
                throw new \Exception("Output tin inventory item not found");
            }
            Log::info("Validated output tin inventory item", [
                'item_id' => $item->id,
                'name' => $item->name,
                'current_quantity' => $item->quantity
            ]);
        }

        // Check input slag inventory item
        if (!empty($processData['inputSlagInventoryItemId'])) {
            $item = InventoryItem::find($processData['inputSlagInventoryItemId']);
            if (!$item) {
                throw new \Exception("Input slag inventory item not found");
            }
            Log::info("Validated input slag inventory item", [
                'item_id' => $item->id,
                'name' => $item->name,
                'current_quantity' => $item->quantity
            ]);
        }

        // Check output slag inventory item
        if (!empty($processData['outputSlagInventoryItemId'])) {
            $item = InventoryItem::find($processData['outputSlagInventoryItemId']);
            if (!$item) {
                throw new \Exception("Output slag inventory item not found");
            }
            Log::info("Validated output slag inventory item", [
                'item_id' => $item->id,
                'name' => $item->name,
                'current_quantity' => $item->quantity
            ]);
        }
    }
} 