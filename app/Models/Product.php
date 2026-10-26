<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'price', 'image', 'alt', 'description', 'featured'])]
class Product extends Model
{
    protected $table = 'products';

    protected $casts = [
        'price' => 'decimal:2',
        'featured' => 'boolean',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}