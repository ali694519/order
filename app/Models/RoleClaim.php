<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleClaim extends Model
{
    use HasFactory;

    protected $table = 'AspNetRoleClaims';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $guarded = [];

    public function role()
    {
        return $this->belongsTo(
            Role::class,
            'RoleId',
            'Id'
        );
    }
}
