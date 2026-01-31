<?php

namespace App\Models;

use NafeesAhmed\Enapi\Model;

class Product extends Model
{
    protected string $table = 'products';
    protected string $resourceName = 'products';
    protected array $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id'
    ];
}
