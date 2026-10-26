<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'customer_email', 'customer_name', 'phone', 'address', 'city', 'status', 'simulated_hours', 'total', 'placed_at'])]
class Order extends Model
{
    protected $table = 'orders';

    protected $casts = [
        'total' => 'decimal:2',
        'simulated_hours' => 'integer',
        'placed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }

    public function getRouteKeyName(): string
    {
        return 'order_id';
    }
}