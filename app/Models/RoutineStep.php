<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['routine_id', 'day', 'title', 'description', 'instructions', 'tips'])]
class RoutineStep extends Model
{
    protected $table = 'routine_steps';

    protected $casts = [
        'day' => 'integer',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}