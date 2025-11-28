<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public static function getRoleNamesByUserId($userId)
    {
        return self::query()
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->pluck('roles.name')
            ->toArray();
    }
}
