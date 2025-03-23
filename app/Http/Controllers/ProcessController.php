<?php

namespace App\Http\Controllers;

use App\Domain\Process\Models\Process;
use App\Domain\Process\Services\ProcessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessController extends Controller
{
    private ProcessService $processService;

    public function __construct(ProcessService $processService)
    {
        $this->processService = $processService;
    }

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
        try {
            $this->processService->validateProcessData($request->all());
            $process = $this->processService->createProcess($request->batch, $request->all());

            return response()->json($process, 201);
        } catch (\Exception $e) {
            Log::error('Error in process creation: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} 