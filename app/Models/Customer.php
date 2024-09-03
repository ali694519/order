<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    protected $fillable =
    [
        'SeqNumber',
        'FullName',
        'Country',
        'Email',
        'PhoneNumber',
        'Address',
        'Fax',
        'WebSite',
        'ExhibitionName',
        'Note'
    ];

    protected $table = 'Customer';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->Id)) {
                $customer->Id = DB::table('Customer')->max('Id') + 1;
            }
        });
    }
}
