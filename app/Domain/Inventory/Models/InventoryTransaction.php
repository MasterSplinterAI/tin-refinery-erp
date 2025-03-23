<?php

namespace App\Domain\Inventory\Models;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'type', // purchase, consumption, production, adjustment
        'quantity',
        'unit_price',
        'currency',
        'reference_type', // batch, purchase_order, adjustment
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    // Relationship with inventory item
    public function inventory_item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // Relationship with batch (if this transaction is related to a batch)
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'reference_id')
            ->where('reference_type', 'batch');
    }
} 