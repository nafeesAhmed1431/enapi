<?php

namespace App\Models;

use NafeesAhmed\Enapi\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $resourceName = 'users';
    protected array $fillable = [
        'name',
        'email',
        'password',
        'status'
    ];
}
