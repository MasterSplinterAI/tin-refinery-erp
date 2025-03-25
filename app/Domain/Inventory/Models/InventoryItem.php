<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Process\Models\Process;
use App\Domain\Inventory\Models\InventoryTransaction;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'quantity',
        'unit',
        'sn_content',
        'location',
        'status',
        'cost_basis_usd',
        'cost_basis_cop',
        'last_purchase_date',
        'last_purchase_price_usd',
        'last_purchase_price_cop'
    ];

    protected $casts = [
        'quantity' => 'float',
        'sn_content' => 'float',
        'cost_basis_usd' => 'decimal:2',
        'cost_basis_cop' => 'decimal:2',
        'last_purchase_price_usd' => 'decimal:2',
        'last_purchase_price_cop' => 'decimal:2',
        'last_purchase_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return InventoryItemFactory::new();
    }

    // Relationship with inventory transactions
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // Relationship with batch processes (for tracking material usage)
    public function batchProcessesAsInput(): HasMany
    {
        return $this->hasMany(Process::class, 'input_inventory_id');
    }

    public function batchProcessesAsOutput(): HasMany
    {
        return $this->hasMany(Process::class, 'output_inventory_id');
    }
} 