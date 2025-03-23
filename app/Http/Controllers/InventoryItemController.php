<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class InventoryItemController extends Controller
{
    public function index()
    {
        $items = InventoryItem::all();
        $transactions = InventoryTransaction::with('inventory_item')
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'items' => $items,
                'transactions' => $transactions
            ]);
        }

        return Inertia::render('Inventory', [
            'items' => $items,
            'transactions' => $transactions,
            'auth' => [
                'user' => Auth::user()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cassiterite,ingot,finished_tin,slag',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|in:kg,ton,pieces',
            'sn_content' => 'required|numeric|min:0|max:100',
            'location' => 'required|string|max:255',
            'status' => 'required|in:active,archived',
        ]);

        $item = InventoryItem::create($validated);

        if ($request->wantsJson()) {
            return response()->json($item, 201);
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item created successfully.');
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cassiterite,ingot,finished_tin,slag',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|in:kg,ton,pieces',
            'sn_content' => 'required|numeric|min:0|max:100',
            'location' => 'required|string|max:255',
            'status' => 'required|in:active,archived',
        ]);

        $inventoryItem->update($validated);

        if ($request->wantsJson()) {
            return response()->json($inventoryItem);
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        if (request()->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('inventory.index')
            ->with('success', 'Inventory item deleted successfully.');
    }
} 