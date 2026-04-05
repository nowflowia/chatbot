<?php

namespace App\Models;

use Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    protected array $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    public static function allActive(): array
    {
        return static::where('status', 'active');
    }

    public static function findBySlug(string $slug): ?array
    {
        return static::findWhere('slug', $slug);
    }
}
