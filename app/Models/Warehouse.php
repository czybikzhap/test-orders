<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
} 