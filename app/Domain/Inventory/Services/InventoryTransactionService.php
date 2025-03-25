<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Batch\Models\Batch;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use App\Domain\Inventory\Events\InventoryTransactionCreated;
use App\Domain\Inventory\Events\InventoryTransactionReversed;
use App\Domain\Inventory\Events\InventoryQuantityChanged;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class InventoryTransactionService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepositoryInterface $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function processBatchTransactions(Batch $batch, array $inputMaterials, array $outputMaterials)
    {
        try {
            DB::beginTransaction();

            // Process input materials (consumption)
            foreach ($inputMaterials as $material) {
                if (!isset($material['inventory_item_id']) || !isset($material['quantity'])) {
                    continue;
                }

                $inventoryItem = $this->inventoryRepository->findOrFail($material['inventory_item_id']);
                $oldQuantity = $inventoryItem->quantity;
                
                // Create consumption transaction
                $transaction = InventoryTransaction::create([
                    'inventory_item_id' => $material['inventory_item_id'],
                    'type' => 'consumption',
                    'quantity' => -$material['quantity'],
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id,
                    'notes' => "Consumed in Batch " . $batch->batchNumber,
                    'currency' => 'USD' // Default currency
                ]);

                // Update inventory quantity
                $this->inventoryRepository->decrementQuantity($inventoryItem, $material['quantity']);

                // Dispatch events
                Event::dispatch(new InventoryTransactionCreated([
                    'transaction_id' => $transaction->id,
                    'inventory_item_id' => $material['inventory_item_id'],
                    'type' => 'consumption',
                    'quantity' => -$material['quantity'],
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id
                ]));

                Event::dispatch(new InventoryQuantityChanged([
                    'inventory_item_id' => $material['inventory_item_id'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $inventoryItem->quantity
                ]));
            }

            // Process output materials (production)
            foreach ($outputMaterials as $material) {
                if (!isset($material['inventory_item_id']) || !isset($material['quantity'])) {
                    continue;
                }

                $inventoryItem = $this->inventoryRepository->findOrFail($material['inventory_item_id']);
                $oldQuantity = $inventoryItem->quantity;

                // Create production transaction
                $transaction = InventoryTransaction::create([
                    'inventory_item_id' => $material['inventory_item_id'],
                    'type' => 'production',
                    'quantity' => $material['quantity'],
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id,
                    'notes' => "Produced in Batch " . $batch->batchNumber,
                    'currency' => 'USD' // Default currency
                ]);

                // Update inventory quantity
                $this->inventoryRepository->incrementQuantity($inventoryItem, $material['quantity']);

                // Dispatch events
                Event::dispatch(new InventoryTransactionCreated([
                    'transaction_id' => $transaction->id,
                    'inventory_item_id' => $material['inventory_item_id'],
                    'type' => 'production',
                    'quantity' => $material['quantity'],
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id
                ]));

                Event::dispatch(new InventoryQuantityChanged([
                    'inventory_item_id' => $material['inventory_item_id'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $inventoryItem->quantity
                ]));
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error processing inventory transactions: " . $e->getMessage());
            throw new Exception("Error processing inventory transactions: " . $e->getMessage());
        }
    }

    public function reverseTransactions(Batch $batch)
    {
        try {
            DB::beginTransaction();

            $transactions = InventoryTransaction::where('reference_type', 'batch')
                ->where('reference_id', $batch->id)
                ->get();

            foreach ($transactions as $transaction) {
                $inventoryItem = $this->inventoryRepository->findOrFail($transaction->inventory_item_id);
                $oldQuantity = $inventoryItem->quantity;

                // Create reversal transaction
                $reversalTransaction = InventoryTransaction::create([
                    'inventory_item_id' => $transaction->inventory_item_id,
                    'type' => 'reversal',
                    'quantity' => -$transaction->quantity, // Reverse the quantity
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id,
                    'notes' => "Reversal of transaction for Batch " . $batch->batchNumber,
                    'currency' => $transaction->currency
                ]);

                // Update inventory quantity
                if ($transaction->type === 'consumption') {
                    $this->inventoryRepository->incrementQuantity($inventoryItem, abs($transaction->quantity));
                } else {
                    $this->inventoryRepository->decrementQuantity($inventoryItem, $transaction->quantity);
                }

                // Dispatch events
                Event::dispatch(new InventoryTransactionReversed([
                    'transaction_id' => $reversalTransaction->id,
                    'original_transaction_id' => $transaction->id,
                    'inventory_item_id' => $transaction->inventory_item_id,
                    'type' => 'reversal',
                    'quantity' => -$transaction->quantity,
                    'reference_type' => 'batch',
                    'reference_id' => $batch->id
                ]));

                Event::dispatch(new InventoryQuantityChanged([
                    'inventory_item_id' => $transaction->inventory_item_id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $inventoryItem->quantity
                ]));
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error reversing inventory transactions: " . $e->getMessage());
            throw new Exception("Error reversing inventory transactions: " . $e->getMessage());
        }
    }

    public function handleBatchTransactions($batch, array $processData): void
    {
        $inputMaterials = [];
        $outputMaterials = [];

        // Collect input materials
        if (!empty($processData['inputTinInventoryItemId']) && !empty($processData['inputTinKilos'])) {
            $inputMaterials[] = [
                'inventory_item_id' => $processData['inputTinInventoryItemId'],
                'quantity' => $processData['inputTinKilos']
            ];
        }

        if (!empty($processData['inputSlagInventoryItemId']) && !empty($processData['inputSlagKilos'])) {
            $inputMaterials[] = [
                'inventory_item_id' => $processData['inputSlagInventoryItemId'],
                'quantity' => $processData['inputSlagKilos']
            ];
        }

        // Collect output materials
        if (!empty($processData['outputTinInventoryItemId']) && !empty($processData['outputTinKilos'])) {
            $outputMaterials[] = [
                'inventory_item_id' => $processData['outputTinInventoryItemId'],
                'quantity' => $processData['outputTinKilos']
            ];
        }

        if (!empty($processData['outputSlagInventoryItemId']) && !empty($processData['outputSlagKilos'])) {
            $outputMaterials[] = [
                'inventory_item_id' => $processData['outputSlagInventoryItemId'],
                'quantity' => $processData['outputSlagKilos']
            ];
        }

        $this->processBatchTransactions($batch, $inputMaterials, $outputMaterials);
    }
} 