<?php

namespace App\Domain\PurchaseOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'status',
        'usd_amount',
        'cop_amount',
        'exchange_rate',
        'exchange_date',
        'xero_invoice_id',
        'notes'
    ];

    protected $casts = [
        'usd_amount' => 'decimal:2',
        'cop_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'exchange_date' => 'datetime'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
} 