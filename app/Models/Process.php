<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Process extends Model
{
    protected $fillable = [
        'batchId',
        'processNumber',
        'processingType',
        'inputTinKilos',
        'inputTinSnContent',
        'inputTinInventoryItemId',
        'outputTinKilos',
        'outputTinSnContent',
        'outputTinInventoryItemId',
        'inputSlagKilos',
        'inputSlagSnContent',
        'inputSlagInventoryItemId',
        'outputSlagKilos',
        'outputSlagSnContent',
        'outputSlagInventoryItemId',
        'notes'
    ];

    protected $casts = [
        'inputTinKilos' => 'decimal:2',
        'inputTinSnContent' => 'decimal:2',
        'outputTinKilos' => 'decimal:2',
        'outputTinSnContent' => 'decimal:2',
        'inputSlagKilos' => 'decimal:2',
        'inputSlagSnContent' => 'decimal:2',
        'outputSlagKilos' => 'decimal:2',
        'outputSlagSnContent' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batchId');
    }

    public function inputTinInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inputTinInventoryItemId');
    }

    public function outputTinInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'outputTinInventoryItemId');
    }

    public function inputSlagInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inputSlagInventoryItemId');
    }

    public function outputSlagInventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'outputSlagInventoryItemId');
    }
}
