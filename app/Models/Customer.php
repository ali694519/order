<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
  use HasFactory;
  protected $fillable =
  [
    'Id',
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
