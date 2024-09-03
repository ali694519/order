<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function get()
    {
        $customers = Customer::select('Id', 'FullName', 'Country', 'PhoneNumber', 'Email')
            ->paginate(10);;
        return response()->json([
            'data' => $customers
        ]);
    }
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
        $customer = Customer::create($validatedData);
        return response()->json(['message' => 'Customer created successfully', 'data' => $customer], 201);
    }

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

        return response()->json(['message' => 'Customer updated successfully', 'client' => $customer]);
    }

    public function show($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        return response()->json($customer);
    }

    public function destroy($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }
}