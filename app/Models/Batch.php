<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'batchNumber',
        'date',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'batchId');
    }
}
