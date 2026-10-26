<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'product_id', 'name', 'category', 'price', 'image', 'quantity'])]
class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}