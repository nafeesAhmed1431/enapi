<?php

namespace App\Models;

use NafeesAhmed\Enapi\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected string $resourceName = 'posts';
    protected array $fillable = [
        'title',
        'content',
        'author_id',
        'status',
        'published_at'
    ];
}
