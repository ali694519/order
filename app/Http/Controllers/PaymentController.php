<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
  /**
   * @OA\Post(
   *     path="/orders/{orderId}/payments",
   *     summary="Add payment to an order",
   *     tags={"Payments"},
   *     @OA\Parameter(
   *         name="orderId",
   *         in="path",
   *         required=true,
   *         description="ID of the order",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"AmountPaid", "PaymentMethod", "PaymentDate"},
   *             @OA\Property(property="AmountPaid", type="number", format="float", example=150.50),
   *             @OA\Property(property="PaymentMethod", type="integer", enum={0, 1, 2}, description="0: Cash, 1: Visa, 2: Bank", example=1),
   *             @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-07")
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Payment added successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Payment added successfully"),
   *             @OA\Property(property="payment", type="object",
   *                 @OA\Property(property="OrderId", type="integer", example=1),
   *                 @OA\Property(property="AmountPaid", type="number", format="float", example=150.50),
   *                 @OA\Property(property="PaymentMethod", type="integer", example=1),
   *                 @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-07")
   *             ),
   *             @OA\Property(property="totalPaid", type="number", format="float", example=300.00),
   *             @OA\Property(property="orderStatus", type="integer", example=1)
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Payment amount exceeds the total order amount",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Payment amount exceeds the total order amount")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order not found")
   *         )
   *     )
   * )
   */
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
  /**
   * @OA\Get(
   *     path="/customers/{customerId}/statement",
   *     summary="Get customer statement by customer ID",
   *     tags={"Customers"},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         required=true,
   *         description="ID of the customer",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Customer statement retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="customer_id", type="integer", example=1),
   *             @OA\Property(property="orders", type="array",
   *                 @OA\Items(
   *                     @OA\Property(property="order_id", type="integer", example=10),
   *                     @OA\Property(property="order_total", type="number", format="float", example=500.00),
   *                     @OA\Property(property="total_paid", type="number", format="float", example=300.00),
   *                     @OA\Property(property="remaining_amount", type="number", format="float", example=200.00),
   *                     @OA\Property(property="payments", type="array",
   *                         @OA\Items(
   *                             @OA\Property(property="AmountPaid", type="number", format="float", example=150.00),
   *                             @OA\Property(property="PaymentMethod", type="integer", description="0: Cash, 1: Visa, 2: Bank", example=1),
   *                             @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-08")
   *                         )
   *                     )
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="No orders found for this customer",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="No orders found for this customer")
   *         )
   *     )
   * )
   */
  public function getCustomerStatementByCustomerId($customerId)
  {
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
