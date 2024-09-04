<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'AspNetRoles';
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'AspNetUserRoles',
            'RoleId',
            'UserId'
        );
    }
}
