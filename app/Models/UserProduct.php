<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'user_email', 'product_id', 'product_name', 'product_type', 'product_image', 'started_at', 'current_day', 'total_days', 'routine_id'])]
class UserProduct extends Model
{
    protected $table = 'user_products';

    protected $casts = [
        'started_at' => 'datetime',
        'current_day' => 'integer',
        'total_days' => 'integer',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}