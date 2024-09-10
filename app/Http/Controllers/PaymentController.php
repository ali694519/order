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


  /**
   * @OA\Get(
   *     path="/api/payments/paid-orders",
   *     summary="Get paid orders by date",
   *     description="Retrieve all orders that have been paid in full on a specific date.",
   *     tags={"Payments"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="date",
   *         in="query",
   *         description="Date to filter the paid orders. Format should be YYYY-MM-DD.",
   *         required=true,
   *         @OA\Schema(type="string", format="date")
   *     ),
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of items per page.",
   *         required=false,
   *         @OA\Schema(type="integer", default=5)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number for pagination.",
   *         required=false,
   *         @OA\Schema(type="integer", default=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Paid orders retrieved successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(
   *                 @OA\Property(property="Number", type="string"),
   *                 @OA\Property(property="order_date", type="string", format="date"),
   *                 @OA\Property(property="payment_date", type="string", format="date"),
   *                 @OA\Property(property="sub_total", type="number", format="float"),
   *                 @OA\Property(property="Discount", type="number", format="float"),
   *                 @OA\Property(property="total", type="number", format="float"),
   *                 @OA\Property(property="customer_name", type="string")
   *             )),
   *             @OA\Property(property="total_amount", type="number", format="float"),
   *             @OA\Property(property="final_amount", type="number", format="float")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="No payments found for the specified date",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="No payments found for this date")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthorized",
   *         @OA\JsonContent(
   *             @OA\Property(property="error", type="string", example="Unauthorized")
   *         )
   *     )
   * )
   */

  public function getPaidOrdersByDate(Request $request)
  {
    $perPage = $request->query('per_page', 5);
    $page = $request->query('page', 1);
    $date = $request->query('date');
    if (!$date) {
      return response()->json([
        'message' => 'Date parameter is required'
      ], 400);
    }
    $payments = Payment::where('PaymentDate', $date)->get();
    if ($payments->isEmpty()) {
      return response()->json([
        'message' => 'No payments found for this date'
      ], 404);
    }
    $orderIds = $payments->pluck('OrderId')->unique();
    $orders = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,CountOfMeters,MeterPrice'
    ])
      ->whereIn('Id', $orderIds)
      ->where('IsDeleted', false)
      ->paginate($perPage, ['*'], 'page', $page);
    $formattedOrders  = $orders->map(function ($order) use ($date, $payments) {
      $sub_total = $order->items->sum(function ($item) {
        return $item->CountOfMeters * $item->MeterPrice;
      });
      $total = $sub_total - $order->Discount;
      $totalPaid = $payments->where('OrderId', $order->Id)->sum('AmountPaid');
      if ($totalPaid >= $total) {
        return [
          'Number' => $order->Number,
          'order_date' => $order->Date,
          'payment_date' => $date,
          'sub_total' => $sub_total,
          'Discount' => $order->Discount,
          'total' => $total,
          'customer_name' => $order->customer->FullName,
        ];
      }
    })->filter();
    $data = $orders->setCollection(collect($formattedOrders));
    $totalAmount = $formattedOrders->sum('sub_total');
    $finalAmount = $formattedOrders->sum('total');

    return response()->json([
      'data' => $data,
      'total_amount' => $totalAmount,
      'final_amount' => $finalAmount,
    ]);
  }
}
