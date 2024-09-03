<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function getInfo($customerId)
    {
        $orders = Order::with(['customer:Id,FullName', 'items:Id,OrderId,CountOfMeters,MeterPrice'])
            ->where('CustomerId', $customerId)
            ->get();

        $filteredOrders = $orders->map(function ($order) {
            $sub_total = $order->items->sum(function ($item) {
                return $item->CountOfMeters * $item->MeterPrice;
            });

            $total = $sub_total - $order->Discount;

            return [
                'Number' => $order->Number,
                'Date' => $order->Date,
                'PaymentDate' => $order->PaymentDate,
                'sub_total' => $sub_total,
                'Discount' => $order->Discount,
                'total' => $total,
                'Note' => $order->Note,
                'CustomerFullName' => $order->customer->FullName,
            ];
        });

        return response()->json([
            'orders' => $filteredOrders
        ]);
    }
    public function store(Request $request, $customerId)
    {
        $validatedData = $request->validate([
            'Discount' => 'nullable|numeric|min:0',
            'Date' => 'nullable|date',
            'PaymentDate' => 'nullable|date',
            'Note' => 'nullable|string',
            'IsPaid' => 'nullable|boolean',
            'Items' => 'required|array',
            'Items.*.Catalog' => 'required|string',
            'Items.*.ColorNumber' => 'required|integer',
            'Items.*.CountOfMeters' => 'required|numeric',
            'Items.*.MeterPrice' => 'required|numeric',
            'Items.*.Note' => 'nullable|string',
        ]);
        $order = new Order();
        $order->CustomerId = $customerId;
        $order->Discount = $validatedData['Discount'] ?? 0;
        $order->Date = now();
        $order->Note = $validatedData['Note'] ?? null;
        $order->IsPaid = $validatedData['IsPaid'] ?? false;
        $order->PaymentDate = $order->IsPaid ? now() : $validatedData['PaymentDate'] ?? null;
        $order->IsDeleted = false;
        $order->save();
        foreach ($validatedData['Items'] as $itemData) {
            $item = new Item($itemData);
            $item->OrderId = $order->Id;
            $item->save();
        }
        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
            'items' => $order->items
        ], 201);
    }
}
