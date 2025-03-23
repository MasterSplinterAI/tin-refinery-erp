<?php

namespace App\Domain\Inventory\Repositories;

use App\Domain\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface
{
    public function find(int $id): ?InventoryItem;
    public function findOrFail(int $id): InventoryItem;
    public function getAll(): Collection;
    public function create(array $data): InventoryItem;
    public function update(InventoryItem $item, array $data): bool;
    public function delete(int $id): bool;
    public function incrementQuantity(InventoryItem $item, float $amount): bool;
    public function decrementQuantity(InventoryItem $item, float $amount): bool;
} 