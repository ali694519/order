<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
