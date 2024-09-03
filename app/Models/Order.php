<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'CustomerId',
        'Discount',
        'Date',
        'Note',
        'Number',
        'IsPaid',
        'PaymentDate',
        'IsDeleted'
    ];
    protected $table = 'Orders';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            do {
                $Number = mt_rand(100000, 999999);
            } while (self::where('Number', $Number)->exists());

            $order->Number = $Number;
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerId', 'Id');
    }
    public function items()
    {
        return $this->hasMany(Item::class, 'OrderId', 'Id');
    }
}
