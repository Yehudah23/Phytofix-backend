<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'body', 'data', 'is_read'])]
class Notification extends Model
{
    protected $table = 'notifications';

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}