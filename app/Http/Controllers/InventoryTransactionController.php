<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryTransaction::with('inventory_item')
            ->latest();

        // Filter by inventory item if provided
        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range if provided
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric',
            'unit_price' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $inventoryItem = InventoryItem::findOrFail($request->inventory_item_id);
            
            // Create the transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $request->inventory_item_id,
                'type' => 'adjustment',
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'currency' => $request->currency,
                'reference_type' => 'manual_adjustment',
                'notes' => $request->notes
            ]);

            // Update inventory item quantity
            $inventoryItem->quantity += $request->quantity;
            $inventoryItem->save();

            DB::commit();

            return redirect()->route('inventory.index');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing transaction: ' . $e->getMessage());
        }
    }

    public function show(InventoryTransaction $transaction)
    {
        return response()->json($transaction->load('inventory_item'));
    }

    public function update(Request $request, InventoryTransaction $transaction)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $transaction->update($request->only(['notes']));

        return response()->json($transaction->load('inventory_item'));
    }

    public function destroy(InventoryTransaction $transaction)
    {
        try {
            DB::beginTransaction();

            // Reverse the quantity change
            $inventoryItem = $transaction->inventory_item;
            $inventoryItem->quantity -= $transaction->quantity;
            $inventoryItem->save();

            // Create a reversal transaction
            InventoryTransaction::create([
                'inventory_item_id' => $transaction->inventory_item_id,
                'type' => 'reversal',
                'quantity' => -$transaction->quantity,
                'unit_price' => $transaction->unit_price,
                'currency' => $transaction->currency,
                'reference_type' => 'transaction_reversal',
                'reference_id' => $transaction->id,
                'notes' => 'Reversal of transaction #' . $transaction->id
            ]);

            // Delete the original transaction
            $transaction->delete();

            DB::commit();

            return response()->json(['message' => 'Transaction reversed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error reversing transaction: ' . $e->getMessage()], 500);
        }
    }

    // Helper method for batch-related transactions
    public function processBatchTransactions(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'input_transactions' => 'required|array',
            'input_transactions.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'input_transactions.*.quantity' => 'required|numeric|min:0.01',
            'output_transactions' => 'required|array',
            'output_transactions.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'output_transactions.*.quantity' => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function() use ($validated) {
            $transactions = [];

            // Process input (consumption) transactions
            foreach ($validated['input_transactions'] as $input) {
                $item = InventoryItem::findOrFail($input['inventory_item_id']);
                
                if ($item->quantity < $input['quantity']) {
                    throw new \Exception("Insufficient quantity for {$item->name}");
                }

                $transaction = $this->createBatchTransaction(
                    $item,
                    'consumption',
                    -$input['quantity'],
                    $validated['batch_id']
                );
                $transactions[] = $transaction;
            }

            // Process output (production) transactions
            foreach ($validated['output_transactions'] as $output) {
                $item = InventoryItem::findOrFail($output['inventory_item_id']);
                
                $transaction = $this->createBatchTransaction(
                    $item,
                    'production',
                    $output['quantity'],
                    $validated['batch_id']
                );
                $transactions[] = $transaction;
            }

            return response()->json($transactions, 201);
        });
    }

    private function createBatchTransaction($item, $type, $quantity, $batchId)
    {
        $transaction = $item->transactions()->create([
            'type' => $type,
            'quantity' => $quantity,
            'currency' => $item->currency,
            'reference_type' => 'batch',
            'reference_id' => $batchId,
            'notes' => "Batch #{$batchId} {$type}",
        ]);

        $item->quantity += $quantity;
        $item->save();

        return $transaction;
    }
} 