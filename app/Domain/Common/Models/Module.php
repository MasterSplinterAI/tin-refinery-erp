<?php

namespace App\Domain\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function accountMappings(): HasMany
    {
        return $this->hasMany(AccountMapping::class);
    }
} 