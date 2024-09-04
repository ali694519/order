<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'AspNetUsers';
    protected $primaryKey = 'Id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $hidden = [
        'PasswordHash',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'AspNetUserRoles',
            'UserId',
            'RoleId'
        );
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            $user->Id = (string)Str::uuid();
            $user->UserName = $user->Email;
            $user->NormalizedUserName = Str::upper($user->Email);
            $user->NormalizedEmail = Str::upper($user->Email);
            $user->SecurityStamp = Str::uuid();
            $user->ConcurrencyStamp = Str::uuid();
            $user->AccessFailedCount = 0;
            $user->EmailConfirmed = true;
            $user->LockoutEnabled = true;
            $user->TwoFactorEnabled = false;
            $user->PhoneNumberConfirmed = false;
        });
    }
}
