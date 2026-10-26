<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['routine_id', 'product_id', 'product_name', 'product_type', 'product_image', 'name', 'description', 'total_days', 'created_by'])]
class ProductRoutine extends Model
{
    protected $table = 'product_routines';

    protected $casts = [
        'total_days' => 'integer',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(RoutineStep::class, 'routine_id');
    }

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }

    public function getRouteKeyName(): string
    {
        return 'routine_id';
    }
}