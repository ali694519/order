<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use Illuminate\Http\Request;

class CatalogsController extends Controller
{
    public function get(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);
        $catalogs = Catalog::select('Id', 'Name', 'Price')
            ->withSum('quantities as total_meters', 'Quantity')
            ->paginate($perPage, ['*'], 'page', $page);
        return response()->json([
            'data' => $catalogs
        ]);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'Name' => 'required|string|max:255',
            'Price' => 'required|numeric|min:0',
        ]);

        $catalog = Catalog::create([
            'Name' => $validatedData['Name'],
            'Price' => $validatedData['Price'],
        ]);

        return response()->json([
            'message' => 'Catalog created successfully!',
            'data' => $catalog
        ], 201);
    }

    public function update(Request $request, $catalog)
    {
        $catalog = Catalog::find($catalog);
        if (!$catalog) {
            return response()->json(['message' => 'Catalog not found'], 404);
        }
        $validatedData = $request->validate([
            'Name' => 'sometimes|required|string|max:255',
            'Price' => 'sometimes|required|numeric|min:0',
        ]);

        $catalog->update($validatedData);
        $catalog->save();

        return response()->json([
            'message' => 'Catalog updated successfully!',
            'data' => $catalog
        ]);
    }

    public function show($catalog)
    {
        $catalog = Catalog::find($catalog);
        if (!$catalog) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        return response()->json($catalog);
    }


    public function delete($catalog)
    {
        $catalog = Catalog::find($catalog);
        if (!$catalog) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        $catalog->delete();
        return response()->json([
            'message' => 'Catalog deleted successfully!'
        ]);
    }
}
