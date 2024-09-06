<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Catalog;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function addColor(Request $request, $catalogId)
    {
        $catalogExists = Catalog::find($catalogId);
        if (!$catalogExists) {
            return response()->json([
                'message' => 'Catalog not found',
            ], 404);
        }
        $validatedData = $request->validate([
            'colors' => 'required|array',
            'colors.*.Name' => 'required|string|max:50',
            'colors.*.Quantity' => 'required|numeric|min:0',
        ]);
        $colors = [];
        foreach ($validatedData['colors'] as $colorData) {
            $colors[] = Color::create([
                'CatalogId' => $catalogId,
                'Name' => $colorData['Name'],
                'Quantity' => $colorData['Quantity'],
            ]);
        }

        return response()->json([
            'message' => 'Color added successfully!',
            'data' => $colors
        ], 201);
    }

    public function getColors(Request $request, $catalogId)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);
        $colors = Color::where('CatalogId', $catalogId)
            ->select('Id', 'Name', 'Quantity')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $colors
        ]);
    }
    public function updateColors(Request $request, $catalogId)
    {
        $catalogExists = Catalog::find($catalogId);
        if (!$catalogExists) {
            return response()->json([
                'message' => 'Catalog not found',
            ], 404);
        }
        $validatedData = $request->validate([
            'data' => 'required|array',
            'data.*.Id' => 'required|integer|exists:Colors,Id',
            'data.*.Quantity' => 'required|integer|min:0',
        ]);
        $colors = Color::where('CatalogId', $catalogId)->get();
        foreach ($validatedData['data']  as $data) {
            $color = $colors->where('Id', $data['Id'])->first();
            $color->Quantity += $data['Quantity'];
            $color->save();
        }
        return response()
            ->json([
                'message' => 'Colors updated successfully',
                'data' => $colors
            ]);
    }
}
