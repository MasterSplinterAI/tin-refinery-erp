<?php

namespace App\Domain\Inventory\Repositories;

use App\Domain\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function find(int $id): ?InventoryItem
    {
        return InventoryItem::find($id);
    }

    public function findOrFail(int $id): InventoryItem
    {
        return InventoryItem::findOrFail($id);
    }

    public function getAll(): Collection
    {
        return InventoryItem::all();
    }

    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function update(InventoryItem $item, array $data): bool
    {
        return $item->update($data);
    }

    public function delete(int $id): bool
    {
        return InventoryItem::destroy($id) > 0;
    }

    public function incrementQuantity(InventoryItem $item, float $amount): bool
    {
        return DB::transaction(function () use ($item, $amount) {
            return $item->increment('quantity', $amount);
        });
    }

    public function decrementQuantity(InventoryItem $item, float $amount): bool
    {
        return DB::transaction(function () use ($item, $amount) {
            if ($item->quantity < $amount) {
                throw new \Exception("Insufficient quantity available");
            }
            return $item->decrement('quantity', $amount);
        });
    }
} 