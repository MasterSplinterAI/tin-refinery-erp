<?php

namespace Tests\Feature\Domain\Inventory;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Services\InventoryTransactionService;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryTransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = app(InventoryTransactionService::class);
    }

    public function test_process_batch_transactions()
    {
        // Create test inventory items
        $inputItem = InventoryItem::factory()->create([
            'quantity' => 1000,
            'name' => 'Raw Tin',
            'type' => 'cassiterite',
            'unit' => 'kg',
            'status' => 'active'
        ]);

        $outputItem = InventoryItem::factory()->create([
            'quantity' => 0,
            'name' => 'Refined Tin',
            'type' => 'ingot',
            'unit' => 'kg',
            'status' => 'active'
        ]);

        // Create a test batch
        $batch = Batch::factory()->create([
            'batchNumber' => 'TEST-001',
            'status' => 'in_progress'
        ]);

        // Define materials for the batch
        $inputMaterials = [
            [
                'inventory_item_id' => $inputItem->id,
                'quantity' => 100
            ]
        ];

        $outputMaterials = [
            [
                'inventory_item_id' => $outputItem->id,
                'quantity' => 95 // Assuming 5% loss in processing
            ]
        ];

        // Process the batch transactions
        $this->transactionService->processBatchTransactions($batch, $inputMaterials, $outputMaterials);

        // Assert input material quantity was reduced
        $this->assertEquals(
            900,
            $inputItem->fresh()->quantity,
            'Input material quantity should be reduced by 100'
        );

        // Assert output material quantity was increased
        $this->assertEquals(
            95,
            $outputItem->fresh()->quantity,
            'Output material quantity should be increased by 95'
        );

        // Assert transactions were created
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $inputItem->id,
            'type' => 'consumption',
            'quantity' => -100,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'currency' => 'USD'
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $outputItem->id,
            'type' => 'production',
            'quantity' => 95,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'currency' => 'USD'
        ]);
    }

    public function test_reverse_batch_transactions()
    {
        // Create test inventory items
        $inputItem = InventoryItem::factory()->create([
            'quantity' => 900,
            'name' => 'Raw Tin',
            'type' => 'cassiterite',
            'unit' => 'kg',
            'status' => 'active'
        ]);

        $outputItem = InventoryItem::factory()->create([
            'quantity' => 95,
            'name' => 'Refined Tin',
            'type' => 'ingot',
            'unit' => 'kg',
            'status' => 'active'
        ]);

        // Create a test batch
        $batch = Batch::factory()->create([
            'batchNumber' => 'TEST-002',
            'status' => 'completed'
        ]);

        // Create test transactions
        InventoryTransaction::create([
            'inventory_item_id' => $inputItem->id,
            'type' => 'consumption',
            'quantity' => -100,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'notes' => "Consumed in Batch TEST-002",
            'currency' => 'USD'
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $outputItem->id,
            'type' => 'production',
            'quantity' => 95,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'notes' => "Produced in Batch TEST-002",
            'currency' => 'USD'
        ]);

        // Reverse the transactions
        $this->transactionService->reverseTransactions($batch);

        // Assert quantities were restored
        $this->assertEquals(
            1000,
            $inputItem->fresh()->quantity,
            'Input material quantity should be restored to original amount'
        );

        $this->assertEquals(
            0,
            $outputItem->fresh()->quantity,
            'Output material quantity should be reduced to 0'
        );

        // Assert reversal transactions were created
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $inputItem->id,
            'type' => 'reversal',
            'quantity' => 100,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'currency' => 'USD'
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $outputItem->id,
            'type' => 'reversal',
            'quantity' => -95,
            'reference_type' => 'batch',
            'reference_id' => $batch->id,
            'currency' => 'USD'
        ]);
    }
} 