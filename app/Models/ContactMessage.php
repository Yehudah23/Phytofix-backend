<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'subject', 'message', 'is_read'])]
class ContactMessage extends Model
{
    protected $casts = ['is_read' => 'boolean'];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}
