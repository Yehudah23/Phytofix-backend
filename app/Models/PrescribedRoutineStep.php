<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['prescribed_routine_id', 'day', 'title', 'description', 'instructions', 'tips'])]
class PrescribedRoutineStep extends Model
{
    protected $table = 'prescribed_routine_steps';

    protected $casts = [
        'day' => 'integer',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}