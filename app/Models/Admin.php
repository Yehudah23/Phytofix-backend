<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
class Admin extends Model
{
    use HasApiTokens;

    protected $table = 'admins';

    protected $hidden = ['password'];

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}