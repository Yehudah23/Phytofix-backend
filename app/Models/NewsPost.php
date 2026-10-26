<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['news_id', 'tag', 'title', 'excerpt', 'date', 'content', 'image', 'created_by'])]
class NewsPost extends Model
{
    protected $table = 'news_posts';

    public function getIdAttribute($value): string
    {
        return (string) $value;
    }

    public function getRouteKeyName(): string
    {
        return 'news_id';
    }
}