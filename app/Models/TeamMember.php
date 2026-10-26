<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'role', 'bio', 'image', 'is_active', 'sort_order'])]
class TeamMember extends Model
{
    protected $table = 'team_members';

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}