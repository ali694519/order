<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function addPayment(Request $request, $orderId)
    {
        $validatedData = $request->validate([
            'AmountPaid' => 'required|numeric|min:0',
            'PaymentMethod' => 'required|integer|in:0,1,2',
            'PaymentDate' => 'required|date',
        ]);

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'message' =>
                'Order not found'
            ], 404);
        }

        $totalPayments = Payment::where('OrderId', $orderId)->sum('AmountPaid');
        $orderTotal = $order->items->sum(function ($item) {
            return $item->CountOfMeters * $item->MeterPrice;
        });
        $totals = $orderTotal - $order->Discount;

        if (($totalPayments + $validatedData['AmountPaid']) > $totals) {
            return response()->json([
                'message' =>
                'Payment amount exceeds the total order amount'
            ], 400);
        }

        $payment = new Payment();
        $payment->OrderId = $orderId;
        $payment->AmountPaid = $validatedData['AmountPaid'];
        $payment->PaymentMethod = $validatedData['PaymentMethod'];
        $payment->PaymentDate = $validatedData['PaymentDate'];
        $payment->save();

        if (($totalPayments + $validatedData['AmountPaid']) >= $totals) {
            $order->Status = 2; // Paid
        } elseif ($totalPayments > 0) {
            $order->Status = 1; // Partial
        } else {
            $order->Status = 0; // Draft
        }
        $order->save();
        return response()->json([
            'message' => 'Payment added successfully',
            'payment' => $payment,
            'totalPaid' => $totalPayments + $validatedData['AmountPaid'],
            'orderStatus' => $order->Status,
        ], 201);
    }

    public function getCustomerStatementByCustomerId(Request $request, $customerId)
    {
        // $perPage = $request->input('per_page', 5);
        // $page = $request->input('page', 1);

        $orders = Order::where('CustomerId', $customerId)
            ->with('items')
            ->get();
        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for this customer'], 404);
        }

        $customerStatement = [];

        foreach ($orders as $order) {
            $orderTotal = $order->items->sum(function ($item) {
                return $item->CountOfMeters * $item->MeterPrice;
            }) - $order->Discount;

            $totalPayments = Payment::where('OrderId', $order->Id)->sum('AmountPaid');

            $remainingAmount = $orderTotal - $totalPayments;

            $customerStatement[] = [
                'order_id' => $order->Id,
                'order_total' => $orderTotal,
                'total_paid' => $totalPayments,
                'remaining_amount' => $remainingAmount,
                'payments' => Payment::where('OrderId', $order->Id)
                    ->get(['AmountPaid', 'PaymentMethod', 'PaymentDate']),
            ];
        }

        return response()->json([
            'customer_id' => $customerId,
            'orders' => $customerStatement
        ]);
    }
}
