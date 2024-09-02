<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
    'client_id',
    'invoice_number',
    'catalog_id',
    'quantity_id',
    'meters_requested',
    'price_per_meter',
    'discount',
    'total',
    'net_total',
    'notes'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            do {
                $invoiceNumber = mt_rand(100000, 999999);
            } while (self::where('invoice_number', $invoiceNumber)->exists());

            $order->invoice_number = $invoiceNumber;
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function quantity()
    {
        return $this->belongsTo(Quantity::class);
    }
}
