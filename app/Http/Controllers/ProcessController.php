<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    public function index()
    {
        return Process::all();
    }

    public function show(Process $process)
    {
        return $process;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'process_type' => 'required|string',
            'status' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
            'input_quantity' => 'required|numeric',
            'output_quantity' => 'required|numeric',
            'waste_quantity' => 'required|numeric'
        ]);

        $process = Process::create([
            'batchId' => $validated['batch_id'],
            'processNumber' => 1, // Default to 1 for now
            'processingType' => $validated['process_type'],
            'inputTinKilos' => $validated['input_quantity'],
            'outputTinKilos' => $validated['output_quantity'],
            'notes' => $validated['notes']
        ]);

        return response()->json($process, 201);
    }
} 