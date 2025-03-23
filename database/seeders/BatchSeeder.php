<?php

namespace Database\Seeders;

use App\Domain\Batch\Models\Batch;
use App\Domain\Process\Models\Process;
use App\Domain\Inventory\Models\InventoryItem;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        // Get our inventory items
        $cassiterite = InventoryItem::where('type', 'cassiterite')->first();
        $ingot = InventoryItem::where('type', 'ingot')->first();
        $finishedTin = InventoryItem::where('type', 'finished_tin')->first();
        $slag = InventoryItem::where('type', 'slag')->first();

        // Create a completed batch
        $completedBatch = Batch::create([
            'batchNumber' => '220324-001',
            'date' => now(),
            'status' => 'completed',
            'notes' => 'First test batch'
        ]);

        // Add a process to the completed batch
        Process::create([
            'batchId' => $completedBatch->id,
            'processNumber' => 1,
            'processingType' => 'kaldo_furnace',
            'inputTinKilos' => 100,
            'inputTinSnContent' => 75.0,
            'inputTinInventoryItemId' => $cassiterite->id,
            'outputTinKilos' => 80,
            'outputTinSnContent' => 99.9,
            'outputTinInventoryItemId' => $ingot->id,
            'inputSlagKilos' => 0,
            'inputSlagSnContent' => 0,
            'outputSlagKilos' => 20,
            'outputSlagSnContent' => 5.0,
            'outputSlagInventoryItemId' => $slag->id,
            'notes' => 'First process in batch'
        ]);

        // Create an in-progress batch
        $inProgressBatch = Batch::create([
            'batchNumber' => '220324-002',
            'date' => now(),
            'status' => 'in_progress',
            'notes' => 'Second test batch'
        ]);

        // Add a process to the in-progress batch
        Process::create([
            'batchId' => $inProgressBatch->id,
            'processNumber' => 1,
            'processingType' => 'refining_kettle',
            'inputTinKilos' => 50,
            'inputTinSnContent' => 99.9,
            'inputTinInventoryItemId' => $ingot->id,
            'outputTinKilos' => 48,
            'outputTinSnContent' => 99.99,
            'outputTinInventoryItemId' => $finishedTin->id,
            'inputSlagKilos' => 0,
            'inputSlagSnContent' => 0,
            'outputSlagKilos' => 2,
            'outputSlagSnContent' => 5.0,
            'outputSlagInventoryItemId' => $slag->id,
            'notes' => 'Refining process'
        ]);
    }
} 