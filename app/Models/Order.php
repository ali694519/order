<?php

namespace App\Models;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'CustomerId',
        'Discount',
        'Date',
        'Note',
        'Number',
        'Status',
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
        return $this->belongsTo(
            Customer::class,
            'CustomerId',
            'Id'
        );
    }
    public function items()
    {
        return $this->hasMany(
            Item::class,
            'OrderId',
            'Id'
        );
    }
    public function payments()
    {
        return $this->hasMany(
            Payment::class,
            'OrderId'
        );
    }

    public function formatOrderDetails()
    {
        $subTotal = $this->items->sum(function ($item) {
            return $item->CountOfMeters * $item->MeterPrice;
        });
        $total = $subTotal - $this->Discount;

        return [
            'order_number' => $this->Number,
            'order_date' => $this->Date,
            'sub_total' => $subTotal,
            'discount' => $this->Discount,
            'total' => $total,
            'note' => $this->Note,
            'customer_name' => $this->customer->FullName,
            'items' => $this->items->map(function ($item) {
                return [
                    'Catalog' => $item->Catalog,
                    'ColorNumber' => $item->ColorNumber,
                    'CountOfMeters' => $item->CountOfMeters,
                    'MeterPrice' => $item->MeterPrice,
                    'item_total' => $item->CountOfMeters * $item->MeterPrice,
                ];
            }),
        ];
    }
}
