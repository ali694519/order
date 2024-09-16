<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
  /**
   * @OA\Get(
   *     path="/api/customers",
   *     summary="Get a list of customers",
   *     description="Returns a paginated list of customers with their details and allows filtering by specific fields",
   *     tags={"Customers"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="per_page",
   *         in="query",
   *         description="Number of items per page",
   *         required=false,
   *         @OA\Schema(type="integer", default=5)
   *     ),
   *     @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="Page number",
   *         required=false,
   *         @OA\Schema(type="integer", default=1)
   *     ),
   *     @OA\Parameter(
   *         name="filter[]",
   *         in="query",
   *         description="Filter customers by specific fields. Example: filter[FullName]=John",
   *         required=false,
   *         @OA\Schema(
   *             type="array",
   *             @OA\Items(type="string"),
   *             example={"FullName=John", "Country=USA"}
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful operation",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(
   *                     property="data",
   *                     type="array",
   *                     @OA\Items(
   *                         type="object",
   *                         @OA\Property(property="Id", type="integer", example=1),
   *                         @OA\Property(property="FullName", type="string", example="John Doe"),
   *                         @OA\Property(property="Country", type="string", example="USA"),
   *                         @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *                         @OA\Property(property="Email", type="string", example="john.doe@example.com")
   *                     )
   *                 ),
   *                 @OA\Property(property="current_page", type="integer", example=1),
   *                 @OA\Property(property="per_page", type="integer", example=5),
   *                 @OA\Property(property="total", type="integer", example=100)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Unauthorized",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Unauthorized")
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Customers not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="No customers found")
   *         )
   *     )
   * )
   */

  public function get(Request $request)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $customers = QueryBuilder::for(Customer::class)
      ->allowedFilters(['Id', 'FullName', 'Country', 'PhoneNumber', 'Email'])
      ->select('Id', 'FullName', 'Country', 'PhoneNumber', 'Email')
      ->paginate($perPage, ['*'], 'page', $page);
    return response()->json([
      'data' => $customers
    ]);
  }

  /**
   * @OA\Post(
   *     path="/api/customers",
   *     summary="Create a new customer",
   *     description="Creates a new customer with the provided details",
   *   * tags={"Customers"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"FullName", "Email", "Country", "PhoneNumber", "Address"},
   *             @OA\Property(property="FullName", type="string", example="John Doe"),
   *             @OA\Property(property="Email", type="string", example="john.doe@example.com"),
   *             @OA\Property(property="Country", type="string", example="USA"),
   *             @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *             @OA\Property(property="Address", type="string", example="123 Main St"),
   *             @OA\Property(property="Note", type="string", example="Preferred customer"),
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Customer created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Customer created successfully"),
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(property="Id", type="integer", example=1),
   *                 @OA\Property(property="FullName", type="string", example="John Doe"),
   *                 @OA\Property(property="Email", type="string", example="john.doe@example.com"),
   *                 @OA\Property(property="Country", type="string", example="USA"),
   *                 @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *                 @OA\Property(property="Address", type="string", example="123 Main St"),
   *                 @OA\Property(property="Note", type="string", example="Preferred customer"),
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid.")
   *         )
   *     )
   * )
   */
  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'FullName' => 'required|string|max:255',
      'Email' => 'required|email|unique:Customer,Email',
      'Country' => 'required|string|max:255',
      'PhoneNumber' => 'required|string|max:20',
      'Address' => 'required|string|max:255',
      'Note' => 'nullable|string|max:1000'
    ]);
    $maxId = DB::table('Customer')->max('Id');
    $validatedData['Id'] = $maxId + 1;
    $customer = Customer::create($validatedData);
    $customer = Customer::find($validatedData['Id']);
    return response()->json([
      'message' => 'Customer created successfully',
      'data' => $customer
    ], 201);
  }

  /**
   * @OA\Post(
   *     path="/api/customers/{customerId}",
   *     summary="Update a customer",
   *     description="Updates an existing customer's details by their ID",
   *  * tags={"Customers"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         description="ID of the customer to update",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=false,
   *         @OA\JsonContent(
   *             @OA\Property(property="FullName", type="string", example="John Doe"),
   *             @OA\Property(property="Email", type="string", example="john.doe@example.com"),
   *             @OA\Property(property="Country", type="string", example="USA"),
   *             @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *             @OA\Property(property="Address", type="string", example="123 Main St"),
   *             @OA\Property(property="Note", type="string", example="Preferred customer"),
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Customer updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Customer updated successfully"),
   *             @OA\Property(
   *                 property="data",
   *                 type="object",
   *                 @OA\Property(property="Id", type="integer", example=1),
   *                 @OA\Property(property="FullName", type="string", example="John Doe"),
   *                 @OA\Property(property="Email", type="string", example="john.doe@example.com"),
   *                 @OA\Property(property="Country", type="string", example="USA"),
   *                 @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *                 @OA\Property(property="Address", type="string", example="123 Main St"),
   *                 @OA\Property(property="Note", type="string", example="Preferred customer"),
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Customer not found",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Customer not found")
   *         )
   *     ),
   *     @OA\Response(
   *         response=400,
   *         description="Validation error",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="The given data was invalid.")
   *         )
   *     )
   * )
   */
  public function update(Request $request, $customerId)
  {
    $customer = Customer::find($customerId);
    if (!$customer) {
      return response()->json(['message' => 'Customer not found'], 404);
    }

    $validatedData = $request->validate([
      'FullName' => 'sometimes|string|max:255',
      'Email' => 'sometimes|email|unique:Customer,Email,' . $customer->Id,
      'Country' => 'sometimes|string|max:255',
      'PhoneNumber' => 'sometimes|string|max:20',
      'Address' => 'sometimes|string|max:255',
      'Note' => 'nullable|string|max:1000'
    ]);

    $customer->update($validatedData);

    return response()->json([
      'message' => 'Customer updated successfully',
      'data' => $customer
    ]);
  }
  /**
   * @OA\Get(
   *     path="/api/customers/{customerId}",
   *     summary="Get a customer by ID",
   *     description="Returns a specific customer by their ID",
   * * tags={"Customers"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         description="ID of the customer to retrieve",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful operation",
   *         @OA\JsonContent(
   *             type="object",
   *             @OA\Property(property="Id", type="integer", example=1),
   *             @OA\Property(property="FullName", type="string", example="John Doe"),
   *             @OA\Property(property="Email", type="string", example="john.doe@example.com"),
   *             @OA\Property(property="Country", type="string", example="USA"),
   *             @OA\Property(property="PhoneNumber", type="string", example="123-456-7890"),
   *             @OA\Property(property="Address", type="string", example="123 Main St"),
   *             @OA\Property(property="Note", type="string", example="Preferred customer"),
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
  public function show($customerId)
  {
    $customer = Customer::find($customerId);
    if (!$customer) {
      return response()->json([
        'message' =>
        'Customer not found'
      ], 404);
    }
    return response()->json($customer);
  }
  /**
   * @OA\Delete(
   *     path="/api/customers/{customerId}",
   *     summary="Delete a customer",
   *     description="Deletes a customer by their ID",
   * * tags={"Customers"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="customerId",
   *         in="path",
   *         description="ID of the customer to delete",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Customer deleted successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Customer deleted successfully")
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
  public function destroy($customerId)
  {
    $customer = Customer::find($customerId);
    if (!$customer) {
      return response()->json([
        'message' =>
        'Customer not found'
      ], 404);
    }
    $customer->delete();
    return response()->json([
      'message' =>
      'Customer deleted successfully'
    ]);
  }
}
