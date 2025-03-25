<?php

namespace App\Domain\PurchaseOrder\Models;

use App\Domain\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity',
        'unit_price_cop',
        'total_price_cop',
        'total_price_usd'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price_cop' => 'decimal:2',
        'total_price_cop' => 'decimal:2',
        'total_price_usd' => 'decimal:2'
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
} 