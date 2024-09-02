<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use App\Models\Quantity;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request, $clientId)
    {
        $clientExists = Client::find($clientId);
        if (!$clientExists) {return response()->json(['message' => 'Client not found',], 404);}

        $validatedData = $request->validate([
        'catalog_id' => 'required|exists:catalogs,id',
        'quantity_id' => 'required|exists:quantities,id',
        'meters_requested' => 'required|integer|min:1',
        'discount' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string',
        ]);

        $quantity = Quantity::findOrFail($validatedData['quantity_id']);
        $price_per_meter = $quantity->price_per_meter;
        $total = $validatedData['meters_requested'] * $price_per_meter;
        $discount = $validatedData['discount'] ?? 0;
        $netTotal = $total - $discount;

        $order = Order::create([
        'client_id' => $clientId,
        'catalog_id' => $validatedData['catalog_id'],
        'quantity_id' => $validatedData['quantity_id'],
        'meters_requested' => $validatedData['meters_requested'],
        'discount' => $discount,
        'total' => $total,
        'net_total' => $netTotal,
        'notes' => $validatedData['notes'],
        ]);
        return response()->json([
        'message' => 'Order created successfully!',
        'data' => $order], 201);
    }
}
