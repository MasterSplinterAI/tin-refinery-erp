<?php

namespace App\Domain\Process\Models;

use App\Domain\Batch\Models\Batch;
use App\Domain\Inventory\Models\InventoryItem;
use Database\Factories\ProcessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Process extends Model
{
    use HasFactory;

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

    protected static function newFactory()
    {
        return ProcessFactory::new();
    }

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