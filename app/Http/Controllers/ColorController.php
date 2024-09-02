<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Catalog;
use App\Models\Quantity;
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
            'Name' => $colorData['color'],
            'Quantity' => $colorData['meters'],
        ]);
    }

    return response()->json([
        'message' => 'Color added successfully!',
        'data' => $colors
    ], 201);

    }

    public function getColors(Request $request,$catalogId)
    {
        $colors = Color::where('CatalogId', $catalogId)
            ->select('Id', 'Name', 'Quantity')
            ->get();

        return response()->json([
            'data' => $colors
        ]);
    }
    public function updateColors(Request $request,$catalogId)
    {
        $catalogExists = Catalog::find($catalogId);

        if (!$catalogExists) {
            return response()->json([
                'message' => 'Catalog not found',
            ], 404);
        }
        $colors = Color::where('CatalogId', $catalogId)->get();

        $colorsData = $request->input('data');
        foreach ($colorsData as $data) {
            $color = $colors->where('Id', $data['Id'])->first();
                $color->Quantity += $data['Quantity'];
                $color->save();
        }
        return response()
        ->json(['message' => 'Colors updated successfully']);
    }
}
