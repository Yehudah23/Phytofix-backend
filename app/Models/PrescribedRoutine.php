<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['prescribed_id', 'user_email', 'user_name', 'name', 'description', 'total_days', 'current_day', 'created_by'])]
class PrescribedRoutine extends Model
{
    protected $table = 'prescribed_routines';

    protected $casts = [
        'total_days' => 'integer',
        'current_day' => 'integer',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(PrescribedRoutineStep::class);
    }

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }

    public function getRouteKeyName(): string
    {
        return 'prescribed_id';
    }
}