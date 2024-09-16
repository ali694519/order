<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
  /**
   * @OA\Get(
   *     path="/api/customers/{customerId}/orders",
   *     summary="Get orders for a specific customer",
   *     description="Retrieve a paginated list of orders for a given customer, including order details and calculated totals.",
   * *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         required=true,
   *         description="The ID of the customer for whom the orders are being retrieved.",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         required=false,
   *         description="Number of results per page.",
   *         @OA\Schema(type="integer", example=5)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         required=false,
   *         description="Page number to retrieve.",
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="List of orders for the specified customer.",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(
   *                     property="current_page",
   *                     type="integer",
   *                     example=1
   *                 ),
   *                 @OA\Property(
   *                     property="data",
   *                     type="array",
   *                     @OA\Items(
   *                         type="object",
   *                         @OA\Property(property="Number", type="string"),
   *                         @OA\Property(property="Date", type="string", format="date"),
   *                         @OA\Property(property="PaymentDate", type="string", format="date"),
   *                         @OA\Property(property="sub_total", type="number", format="float"),
   *                         @OA\Property(property="Discount", type="number", format="float"),
   *                         @OA\Property(property="total", type="number", format="float"),
   *                         @OA\Property(property="Note", type="string"),
   *                         @OA\Property(property="customer_name", type="string")
   *                     )
   *                 ),
   *                 @OA\Property(
   *                     property="first_page_url",
   *                     type="string",
   *                     example="http://example.com/api/customers/1/orders?per_page=5&page=1"
   *                 ),
   *                 @OA\Property(
   *                     property="last_page_url",
   *                     type="string",
   *                     example="http://example.com/api/customers/1/orders?per_page=5&page=10"
   *                 ),
   *                 @OA\Property(
   *                     property="next_page_url",
   *                     type="string",
   *                     example="http://example.com/api/customers/1/orders?per_page=5&page=2"
   *                 ),
   *                 @OA\Property(
   *                     property="prev_page_url",
   *                     type="string",
   *                     example="http://example.com/api/customers/1/orders?per_page=5&page=0"
   *                 ),
   *                 @OA\Property(
   *                     property="total",
   *                     type="integer",
   *                     example=100
   *                 ),
   *                 @OA\Property(
   *                     property="total_pages",
   *                     type="integer",
   *                     example=20
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Customer not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Customer not found")
   *         )
   *     )
   * )
   */
  public function getInfo(Request $request, $customerId)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $orders = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,CountOfMeters,MeterPrice'
    ])->where('CustomerId', $customerId)
      ->where('IsDeleted', false)
      ->paginate($perPage, ['*'], 'page', $page);

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
        'customer_name' => $order->customer->FullName,
      ];
    });
    $data = $orders->setCollection(collect($filteredOrders));
    return response()->json([
      'data' => $data
    ]);
  }
  /**
   * @OA\Post(
   *     path="/api/customers/{customerId}/orders",
   *     summary="Create a new order for a customer",
   *     description="Create a new order for a specific customer, including order details and items.",
   *  *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         required=true,
   *         description="The ID of the customer for whom the order is being created.",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"Items"},
   *             @OA\Property(
   *                 property="Discount",
   *                 type="number",
   *                 format="float",
   *                 example=10.0
   *             ),
   *             @OA\Property(
   *                 property="Date",
   *                 type="string",
   *                 format="date",
   *                 example="2024-09-01"
   *             ),
   *             @OA\Property(
   *                 property="PaymentDate",
   *                 type="string",
   *                 format="date",
   *                 example="2024-09-05"
   *             ),
   *             @OA\Property(
   *                 property="Note",
   *                 type="string",
   *                 example="Order note here"
   *             ),
   *             @OA\Property(
   *                 property="status",
   *                 type="integer",
   *                 example=1
   *             ),
   *             @OA\Property(
   *                 property="Items",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     required={"Catalog", "ColorNumber", "CountOfMeters", "MeterPrice"},
   *                     @OA\Property(property="Catalog", type="string", example="Catalog name"),
   *                     @OA\Property(property="ColorNumber", type="integer", example=123),
   *                     @OA\Property(property="CountOfMeters", type="number", format="float", example=10.5),
   *                     @OA\Property(property="MeterPrice", type="number", format="float", example=20.0),
   *                     @OA\Property(property="Note", type="string", example="Item note here")
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Order created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="message",
   *                 type="string",
   *                 example="Order created successfully"
   *             ),
   *             @OA\Property(
   *                 property="order",
   *                 type="object",
   *                 @OA\Property(property="Id", type="integer", example=1),
   *                 @OA\Property(property="CustomerId", type="integer", example=123),
   *                 @OA\Property(property="Discount", type="number", format="float", example=10.0),
   *                 @OA\Property(property="Date", type="string", format="date", example="2024-09-01"),
   *                 @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-05"),
   *                 @OA\Property(property="Note", type="string", example="Order note here"),
   *                 @OA\Property(property="Status", type="integer", example=1),
   *                 @OA\Property(property="IsDeleted", type="boolean", example=false)
   *             ),
   *             @OA\Property(
   *                 property="items",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     @OA\Property(property="Id", type="integer", example=1),
   *                     @OA\Property(property="OrderId", type="integer", example=1),
   *                     @OA\Property(property="Catalog", type="string", example="Catalog name"),
   *                     @OA\Property(property="ColorNumber", type="integer", example=123),
   *                     @OA\Property(property="CountOfMeters", type="number", format="float", example=10.5),
   *                     @OA\Property(property="MeterPrice", type="number", format="float", example=20.0),
   *                     @OA\Property(property="Note", type="string", example="Item note here")
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid.")
   *         )
   *     )
   * )
   */
  public function store(Request $request, $customerId)
  {
    $validatedData = $request->validate([
      'Discount' => 'nullable|numeric|min:0',
      'Date' => 'nullable|date',
      'PaymentDate' => 'nullable|date',
      'Note' => 'nullable|string',
      'status' => 'nullable|integer|in:0,1,2',
      'Items' => 'required|array',
      'Items.*.Catalog' => 'required|string',
      'Items.*.ColorNumber' => 'required|integer',
      'Items.*.CountOfMeters' => 'required|numeric',
      'Items.*.MeterPrice' => 'required|numeric',
      'Items.*.Note' => 'nullable|string',
    ]);

    if (!Customer::find($customerId)) {
      return response()->json(['message' => 'Customer not found'], 404);
    }

    $order = new Order();
    $order->CustomerId = $customerId;
    $order->Discount = $validatedData['Discount'] ?? 0;
    $order->Date = now();
    $order->Note = $validatedData['Note'] ?? null;
    $order->Status = $validatedData['status'] ?? 0;
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
  /**
   * @OA\Get(
   *     path="/customers/order/details",
   *     summary="Get details of a specific customer's order",
   *     description="Retrieve the details of a specific order for a customer by providing customer ID and order ID.",
   *     tags={"Orders"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="CustomerId",
   *         in="query",
   *         description="The ID of the customer",
   *         required=true,
   *         @OA\Schema(
   *             type="integer"
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="OrderId",
   *         in="query",
   *         description="The ID of the order",
   *         required=true,
   *         @OA\Schema(
   *             type="integer"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order details retrieved successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="Id", type="integer"),
   *             @OA\Property(property="Number", type="string"),
   *             @OA\Property(property="Date", type="string", format="date"),
   *             @OA\Property(property="PaymentDate", type="string", format="date"),
   *             @OA\Property(property="Discount", type="number", format="float"),
   *             @OA\Property(property="Note", type="string"),
   *             @OA\Property(property="customer_name", type="string"),
   *             @OA\Property(
   *                 property="items",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     @OA\Property(property="Id", type="integer"),
   *                     @OA\Property(property="Catalog", type="string"),
   *                     @OA\Property(property="ColorNumber", type="integer"),
   *                     @OA\Property(property="CountOfMeters", type="number", format="float"),
   *                     @OA\Property(property="MeterPrice", type="number", format="float"),
   *                     @OA\Property(property="Note", type="string")
   *                 )
   *             )
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
  public function getCustomerOrders(Request $request)
  {
    $validatedData = $request->validate([
      'CustomerId' => 'required|integer',
      'OrderId' => 'required|integer',
    ]);

    $CustomerId = $validatedData['CustomerId'];
    $OrderId = $validatedData['OrderId'];

    $order = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,Catalog,ColorNumber,CountOfMeters,MeterPrice'
    ])->where('CustomerId', $CustomerId)
      ->where('Id', $OrderId)
      ->where('IsDeleted', false)
      ->first();
    if (!$order) {
      return response()->json(['message' => 'Order not found'], 404);
    }
    return response()->json($order->formatOrderDetails());
  }
  /**
   * @OA\Delete(
   *     path="/api/order/delete",
   *     summary="Delete an order",
   *     description="Delete a specific order based on CustomerId and OrderId.",
   *  *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="CustomerId",
   *         in="query",
   *         description="ID of the customer who owns the order.",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Parameter(
   *         name="OrderId",
   *         in="query",
   *         description="ID of the order to be deleted.",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order successfully deleted",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order deleted successfully")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order not found")
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
  public function deleteOrder(Request $request)
  {
    $validatedData = $request->validate([
      'OrderId' => 'required|integer',
    ]);
    $OrderId = $validatedData['OrderId'];
    $order = Order::where('Id', $OrderId)
      ->first();
    if (!$order) {
      return response()->json(['message' => 'Order not found'], 404);
    }
    // $order->delete();
    $order->IsDeleted = true;
    $order->save();

    return response()->json(['message' => 'Order deleted successfully']);
  }

  /**
   * @OA\Delete(
   *     path="/api/order/delete-permanently",
   *     summary="Permanently delete an order",
   *     description="Permanently delete a specific order based on CustomerId and OrderId.",
   *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="CustomerId",
   *         in="query",
   *         description="ID of the customer who owns the order.",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Parameter(
   *         name="OrderId",
   *         in="query",
   *         description="ID of the order to be deleted.",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order permanently deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order permanently deleted successfully")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order not found")
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
  public function deleteOrderPermanently(Request $request)
  {
    $validatedData = $request->validate([
      'OrderId' => 'required|integer',
    ]);

    $OrderId = $validatedData['OrderId'];

    $order = Order::where('Id', $OrderId)
      ->first();

    if (!$order) {
      return response()->json(['message' => 'Order not found'], 404);
    }

    $order->delete();

    return response()->json(['message' => 'Order permanently deleted successfully']);
  }
  /**
   * @OA\Post(
   *     path="/api/orders/update/{orderId}",
   *     summary="Update an existing order",
   *     description="Update details of an existing order including its items.",
   *  *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="orderId",
   *         in="path",
   *         required=true,
   *         description="The ID of the order to update.",
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="Discount",
   *                 type="number",
   *                 format="float",
   *                 example=10.0
   *             ),
   *             @OA\Property(
   *                 property="Date",
   *                 type="string",
   *                 format="date",
   *                 example="2024-09-01"
   *             ),
   *             @OA\Property(
   *                 property="PaymentDate",
   *                 type="string",
   *                 format="date",
   *                 example="2024-09-05"
   *             ),
   *             @OA\Property(
   *                 property="Note",
   *                 type="string",
   *                 example="Updated order note"
   *             ),
   *             @OA\Property(
   *                 property="status",
   *                 type="integer",
   *                 example=1
   *             ),
   *             @OA\Property(
   *                 property="Items",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     required={"Id", "Catalog", "ColorNumber", "CountOfMeters", "MeterPrice"},
   *                     @OA\Property(property="Id", type="integer", example=1),
   *                     @OA\Property(property="Catalog", type="string", example="Updated Catalog"),
   *                     @OA\Property(property="ColorNumber", type="integer", example=123),
   *                     @OA\Property(property="CountOfMeters", type="number", format="float", example=12.5),
   *                     @OA\Property(property="MeterPrice", type="number", format="float", example=25.0),
   *                     @OA\Property(property="Note", type="string", example="Updated item note")
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Order updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="message",
   *                 type="string",
   *                 example="Order updated successfully"
   *             ),
   *             @OA\Property(
   *                 property="order",
   *                 type="object",
   *                 @OA\Property(property="Id", type="integer", example=1),
   *                 @OA\Property(property="CustomerId", type="integer", example=123),
   *                 @OA\Property(property="Discount", type="number", format="float", example=10.0),
   *                 @OA\Property(property="Date", type="string", format="date", example="2024-09-01"),
   *                 @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-05"),
   *                 @OA\Property(property="Note", type="string", example="Updated order note"),
   *                 @OA\Property(property="Status", type="integer", example=1),
   *                 @OA\Property(property="IsDeleted", type="boolean", example=false)
   *             ),
   *             @OA\Property(
   *                 property="items",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     @OA\Property(property="Id", type="integer", example=1),
   *                     @OA\Property(property="OrderId", type="integer", example=1),
   *                     @OA\Property(property="Catalog", type="string", example="Updated Catalog"),
   *                     @OA\Property(property="ColorNumber", type="integer", example=123),
   *                     @OA\Property(property="CountOfMeters", type="number", format="float", example=12.5),
   *                     @OA\Property(property="MeterPrice", type="number", format="float", example=25.0),
   *                     @OA\Property(property="Note", type="string", example="Updated item note")
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=422,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid.")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Order not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Order not found.")
   *         )
   *     )
   * )
   */
  public function update(Request $request, $orderId)
  {
    $validatedData = $request->validate([
      'Discount' => 'nullable|numeric|min:0',
      'Date' => 'nullable|date',
      'PaymentDate' => 'nullable|date',
      'Note' => 'nullable|string',
      'status' => 'nullable|integer|in:0,1,2',
      'Items' => 'required|array',
      'Items.*.Id' => 'required|integer|exists:Items,Id',
      'Items.*.Catalog' => 'required|string',
      'Items.*.ColorNumber' => 'required|integer',
      'Items.*.CountOfMeters' => 'required|numeric',
      'Items.*.MeterPrice' => 'required|numeric',
      'Items.*.Note' => 'nullable|string',
    ]);
    $order = Order::findOrFail($orderId);
    $order->Discount = $validatedData['Discount'] ?? $order->Discount;
    $order->Date = $validatedData['Date'] ?? $order->Date;
    $order->Note = $validatedData['Note'] ?? $order->Note;
    $order->Status = $validatedData['IsPaid'] ?? $order->Status;
    $order->PaymentDate = $order->IsPaid ? now() : $validatedData['PaymentDate'] ?? $order->PaymentDate;
    $order->save();
    foreach ($validatedData['Items'] as $itemData) {
      $item = Item::find($itemData['Id']);
      $item->Catalog = $itemData['Catalog'];
      $item->ColorNumber = $itemData['ColorNumber'];
      $item->CountOfMeters = $itemData['CountOfMeters'];
      $item->MeterPrice = $itemData['MeterPrice'];
      $item->Note = $itemData['Note'] ?? $item->Note;
      $item->save();
    }
    return response()->json([
      'message' => 'Order updated successfully',
      'order' => $order,
      'items' => $order->items
    ], 200);
  }
  /**
   * @OA\Get(
   *     path="/api/orders",
   *     summary="Get a list of orders",
   *     description="Retrieve a paginated list of orders with their customer and item details.",
   *  *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of orders per page.",
   *         required=false,
   *         @OA\Schema(type="integer", example=5)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number for pagination.",
   *         required=false,
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful response",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(
   *                     property="current_page",
   *                     type="integer",
   *                     example=1
   *                 ),
   *                 @OA\Property(
   *                     property="data",
   *                     type="array",
   *                     @OA\Items(
   *                         type="object",
   *                         @OA\Property(property="Number", type="string", example="ORD123"),
   *                         @OA\Property(property="Date", type="string", format="date", example="2024-09-01"),
   *                         @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-05"),
   *                         @OA\Property(property="sub_total", type="number", format="float", example=250.0),
   *                         @OA\Property(property="Discount", type="number", format="float", example=10.0),
   *                         @OA\Property(property="total", type="number", format="float", example=240.0),
   *                         @OA\Property(property="Note", type="string", example="Order note here"),
   *                         @OA\Property(property="customer_name", type="string", example="John Doe")
   *                     )
   *                 ),
   *                 @OA\Property(
   *                     property="first_page_url",
   *                     type="string",
   *                     example="/api/orders?page=1&per_page=5"
   *                 ),
   *                 @OA\Property(
   *                     property="last_page_url",
   *                     type="string",
   *                     example="/api/orders?page=10&per_page=5"
   *                 ),
   *                 @OA\Property(
   *                     property="next_page_url",
   *                     type="string",
   *                     example="/api/orders?page=2&per_page=5"
   *                 ),
   *                 @OA\Property(
   *                     property="prev_page_url",
   *                     type="string",
   *                     example="/api/orders?page=0&per_page=5"
   *                 ),
   *                 @OA\Property(
   *                     property="total",
   *                     type="integer",
   *                     example=50
   *                 ),
   *                 @OA\Property(
   *                     property="per_page",
   *                     type="integer",
   *                     example=5
   *                 ),
   *                 @OA\Property(
   *                     property="last_page",
   *                     type="integer",
   *                     example=10
   *                 )
   *             )
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
  public function get(Request $request)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $orders = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,CountOfMeters,MeterPrice'
    ])->where('IsDeleted', false)
      ->paginate($perPage, ['*'], 'page', $page);

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
        'customer_name' => $order->customer->FullName,
      ];
    });
    $data = $orders->setCollection(collect($filteredOrders));
    return response()->json([
      'data' => $data
    ]);
  }

  /**
   * @OA\Get(
   *     path="/orders/search",
   *     summary="Search orders within a date range",
   *     description="Retrieve orders placed between the specified start date and end date.",
   *     tags={"Orders"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="start_date",
   *         in="query",
   *         description="Start date for the search (format: YYYY-MM-DD)",
   *         required=true,
   *         @OA\Schema(
   *             type="string",
   *             format="date"
   *         )
   *     ),
   *     @OA\Parameter(
   *         name="end_date",
   *         in="query",
   *         description="End date for the search (format: YYYY-MM-DD)",
   *         required=true,
   *         @OA\Schema(
   *             type="string",
   *             format="date"
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Orders retrieved successfully",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(
   *                 property="data",
   *                 type="array",
   *                 @OA\Items(
   *                     type="object",
   *                     @OA\Property(property="Id", type="integer"),
   *                     @OA\Property(property="Number", type="string"),
   *                     @OA\Property(property="Date", type="string", format="date"),
   *                     @OA\Property(property="PaymentDate", type="string", format="date"),
   *                     @OA\Property(property="Discount", type="number", format="float"),
   *                     @OA\Property(property="Note", type="string"),
   *                     @OA\Property(property="customer_name", type="string"),
   *                     @OA\Property(
   *                         property="items",
   *                         type="array",
   *                         @OA\Items(
   *                             type="object",
   *                             @OA\Property(property="Id", type="integer"),
   *                             @OA\Property(property="Catalog", type="string"),
   *                             @OA\Property(property="ColorNumber", type="integer"),
   *                             @OA\Property(property="CountOfMeters", type="number", format="float"),
   *                             @OA\Property(property="MeterPrice", type="number", format="float"),
   *                             @OA\Property(property="Note", type="string")
   *                         )
   *                     )
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Invalid input",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Invalid input")
   *         )
   *     )
   * )
   */
  public function searchOrdersByDate(Request $request)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $validatedData = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $startDate = $validatedData['start_date'];
    $endDate = $validatedData['end_date'];

    $orders = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,Catalog,ColorNumber,CountOfMeters,MeterPrice'
    ])->whereBetween('Date', [$startDate, $endDate])
      ->where('IsDeleted', false)
      ->paginate($perPage, ['*'], 'page', $page);

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
        'customer_name' => $order->customer->FullName,
      ];
    });
    $data = $orders->setCollection(collect($filteredOrders));
    return response()->json([
      'data' => $data
    ]);
  }

  /**
   * @OA\Get(
   *     path="/api/orders/deleted",
   *     summary="Get all deleted orders",
   *     description="Retrieve all orders that have been marked as deleted.",
   *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of results per page.",
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
   *         description="List of deleted orders",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="array", @OA\Items(
   *                 @OA\Property(property="Number", type="string"),
   *                 @OA\Property(property="Date", type="string", format="date-time"),
   *                 @OA\Property(property="PaymentDate", type="string", format="date-time"),
   *                 @OA\Property(property="sub_total", type="number", format="float"),
   *                 @OA\Property(property="Discount", type="number", format="float"),
   *                 @OA\Property(property="total", type="number", format="float"),
   *                 @OA\Property(property="Note", type="string"),
   *                 @OA\Property(property="customer_name", type="string")
   *             ))
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
  public function getDeletedOrders(Request $request)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $orders = Order::with([
      'customer:Id,FullName',
      'items:Id,OrderId,CountOfMeters,MeterPrice'
    ])->where('IsDeleted', true)
      ->paginate($perPage, ['*'], 'page', $page);

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
        'customer_name' => $order->customer->FullName,
      ];
    });
    $data = $orders->setCollection(collect($filteredOrders));
    return response()->json([
      'data' => $data
    ]);
  }
  /**
   * @OA\Patch(
   *     path="/api/orders/restore",
   *     summary="Restore deleted orders for a customer",
   *     description="Restore all orders marked as deleted for a specific customer by setting IsDeleted to false.",
   *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="CustomerId",
   *         in="query",
   *         description="ID of the customer whose deleted orders are to be restored.",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Orders successfully restored",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Orders restored successfully"),
   *             @OA\Property(property="restored_count", type="integer", example=5)
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Orders not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="No deleted orders found for this customer")
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
  public function restoreOrders(Request $request)
  {
    $validatedData = $request->validate([
      'CustomerId' => 'required|integer',
    ]);
    $CustomerId = $validatedData['CustomerId'];
    $orders = Order::where('CustomerId', $CustomerId)
      ->where('IsDeleted', true)
      ->get();
    if ($orders->isEmpty()) {
      return response()->json([
        'message' => 'No deleted orders found for this customer'
      ], 404);
    }
    foreach ($orders as $order) {
      $order->IsDeleted = false;
      $order->save();
    }
    return response()->json([
      'message' => 'Orders restored successfully',
      'restored_count' => $orders->count(),
    ]);
  }
  /**
   * @OA\Get(
   *     path="/api/orders/status",
   *     summary="Get orders by status with pagination.",
   *     description="Retrieve a list of orders by status with pagination.",
   *     tags={"Orders"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="status",
   *         in="query",
   *         description="Order status (0, 1, 2).",
   *         required=true,
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of orders per page.",
   *         required=false,
   *         @OA\Schema(type="integer", example=5)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number for pagination.",
   *         required=false,
   *         @OA\Schema(type="integer", example=1)
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful response",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="current_page",
   *                 type="integer",
   *                 example=1
   *             ),
   *             @OA\Property(
   *                 property="data",
   *                 type="array",
   *                 @OA\Items(
   *                     @OA\Property(property="Number", type="string", example="ORD123"),
   *                     @OA\Property(property="Date", type="string", format="date", example="2024-09-01"),
   *                     @OA\Property(property="PaymentDate", type="string", format="date", example="2024-09-05"),
   *                     @OA\Property(property="sub_total", type="number", format="float", example=250.0),
   *                     @OA\Property(property="Discount", type="number", format="float", example=10.0),
   *                     @OA\Property(property="total", type="number", format="float", example=240.0),
   *                     @OA\Property(property="Note", type="string", example="Order note here"),
   *                     @OA\Property(property="Status", type="integer", example=2),
   *                     @OA\Property(property="customer_name", type="string", example="John Doe")
   *                 )
   *             ),
   *             @OA\Property(property="total", type="integer", example=50),
   *             @OA\Property(property="per_page", type="integer", example=5),
   *             @OA\Property(property="last_page", type="integer", example=10),
   *             @OA\Property(property="first_page_url", type="string", example="/api/orders/status?page=1&per_page=5"),
   *             @OA\Property(property="last_page_url", type="string", example="/api/orders/status?page=10&per_page=5"),
   *             @OA\Property(property="next_page_url", type="string", example="/api/orders/status?page=2&per_page=5"),
   *             @OA\Property(property="prev_page_url", type="string", example="/api/orders/status?page=0&per_page=5"),
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
  public function getByStatus(Request $request)
  {
    $request->validate([
      'status' => 'required|in:0,1,2'
    ]);

    $status = $request->input('status');
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $orders = Order::with(['customer:Id,FullName', 'items:Id,OrderId,CountOfMeters,MeterPrice'])
      ->where('Status', $status)
      ->where('IsDeleted', false)
      ->paginate($perPage, ['*'], 'page', $page);

    $filteredOrders = $orders->getCollection()->map(function ($order) {
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
        'Status' => $order->Status,
        'customer_name' => $order->customer->FullName,
      ];
    });

    $orders->setCollection($filteredOrders);
    return response()->json($orders);
  }
}
