<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMapping extends Model
{
    protected $fillable = [
        'module',
        'module_id',
        'transaction_type',
        'xero_account_code',
        'xero_account_name',
    ];

    public static function getMapping(string $module, ?string $transactionType = null): ?self
    {
        $query = static::query();
        
        // Try to find by module name
        $query->where(function($q) use ($module) {
            $q->where('module', $module)
              ->orWhereHas('module', function($q) use ($module) {
                  $q->where('name', $module);
              });
        });
        
        if ($transactionType !== null) {
            $query->where('transaction_type', $transactionType);
        }
        
        return $query->first();
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }
} 