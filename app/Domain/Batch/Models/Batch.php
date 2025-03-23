<?php

namespace App\Domain\Batch\Models;

use App\Domain\Process\Models\Process;
use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batchNumber',
        'date',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    protected static function newFactory()
    {
        return BatchFactory::new();
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'batchId');
    }
} 