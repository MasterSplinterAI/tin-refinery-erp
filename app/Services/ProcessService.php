<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProcessService
{
    public function createProcess(Batch $batch, array $data): Process
    {
        Log::info('Creating process for batch', [
            'batch_id' => $batch->id,
            'process_data' => $data
        ]);

        $process = new Process([
            'batchId' => $batch->id,
            'processNumber' => $data['processNumber'],
            'processingType' => $data['processingType'],
            'inputTinKilos' => $data['inputTinKilos'] ?? null,
            'inputTinSnContent' => $data['inputTinSnContent'] ?? null,
            'inputTinInventoryItemId' => $data['inputTinInventoryItemId'] ?? null,
            'outputTinKilos' => $data['outputTinKilos'] ?? null,
            'outputTinSnContent' => $data['outputTinSnContent'] ?? null,
            'outputTinInventoryItemId' => $data['outputTinInventoryItemId'] ?? null,
            'inputSlagKilos' => $data['inputSlagKilos'] ?? null,
            'inputSlagSnContent' => $data['inputSlagSnContent'] ?? null,
            'inputSlagInventoryItemId' => $data['inputSlagInventoryItemId'] ?? null,
            'outputSlagKilos' => $data['outputSlagKilos'] ?? null,
            'outputSlagSnContent' => $data['outputSlagSnContent'] ?? null,
            'outputSlagInventoryItemId' => $data['outputSlagInventoryItemId'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);

        $batch->processes()->save($process);
        Log::info('Process created successfully', ['process_id' => $process->id]);

        return $process;
    }

    public function validateProcessData(array $data): bool
    {
        $rules = [
            'processNumber' => 'required|integer',
            'processingType' => 'required|string|in:kaldo_furnace,refining_kettle',
            'inputTinKilos' => 'nullable|numeric',
            'inputTinSnContent' => 'nullable|numeric',
            'inputTinInventoryItemId' => 'nullable|exists:inventory_items,id',
            'outputTinKilos' => 'nullable|numeric',
            'outputTinSnContent' => 'nullable|numeric',
            'outputTinInventoryItemId' => 'nullable|exists:inventory_items,id',
            'inputSlagKilos' => 'nullable|numeric',
            'inputSlagSnContent' => 'nullable|numeric',
            'inputSlagInventoryItemId' => 'nullable|exists:inventory_items,id',
            'outputSlagKilos' => 'nullable|numeric',
            'outputSlagSnContent' => 'nullable|numeric',
            'outputSlagInventoryItemId' => 'nullable|exists:inventory_items,id',
            'notes' => 'nullable|string'
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return true;
    }

    public function calculateYield(Process $process): float
    {
        $inputWeight = ($process->inputTinKilos ?? 0) + ($process->inputSlagKilos ?? 0);
        $outputWeight = ($process->outputTinKilos ?? 0) + ($process->outputSlagKilos ?? 0);

        if ($inputWeight <= 0) {
            return 0;
        }

        return ($outputWeight / $inputWeight) * 100;
    }

    public function calculateSnRecovery(Process $process): float
    {
        $inputSnContent = (($process->inputTinKilos ?? 0) * ($process->inputTinSnContent ?? 0) / 100) +
                         (($process->inputSlagKilos ?? 0) * ($process->inputSlagSnContent ?? 0) / 100);

        $outputSnContent = (($process->outputTinKilos ?? 0) * ($process->outputTinSnContent ?? 0) / 100) +
                          (($process->outputSlagKilos ?? 0) * ($process->outputSlagSnContent ?? 0) / 100);

        if ($inputSnContent <= 0) {
            return 0;
        }

        return ($outputSnContent / $inputSnContent) * 100;
    }
} 