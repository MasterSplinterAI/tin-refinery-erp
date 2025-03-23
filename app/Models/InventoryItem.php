<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'quantity' => 'float',
        'sn_content' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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