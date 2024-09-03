<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'OrderId',
        'Catalog',
        'ColorNumber',
        'CountOfMeters',
        'MeterPrice',
        'Note'
    ];
    protected $table = 'Items';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'OrderId', 'Id');
    }
}
