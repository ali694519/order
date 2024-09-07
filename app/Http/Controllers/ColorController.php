<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Catalog;
use Illuminate\Http\Request;


class ColorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/catalogs/{catalogId}/colors",
     *     summary="Add colors to a catalog",
     *     description="Adds multiple colors to a specific catalog by its ID",
     *  *     tags={"Colors"},
     *     @OA\Parameter(
     *         name="catalogId",
     *         in="path",
     *         description="ID of the catalog to which colors will be added",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"colors"},
     *             @OA\Property(
     *                 property="colors",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"Name", "Quantity"},
     *                     @OA\Property(property="Name", type="string", example="Red"),
     *                     @OA\Property(property="Quantity", type="number", format="float", example=100)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Colors added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Color added successfully!"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="CatalogId", type="integer", example=1),
     *                     @OA\Property(property="Name", type="string", example="Red"),
     *                     @OA\Property(property="Quantity", type="number", format="float", example=100)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Catalog not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Catalog not found")
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Get(
     *     path="/api/catalogs/{catalogId}/colors",
     *     summary="Get colors of a catalog",
     *     description="Retrieves a paginated list of colors for a specific catalog by its ID",
     *   *     tags={"Colors"},
     *     @OA\Parameter(
     *         name="catalogId",
     *         in="path",
     *         description="ID of the catalog for which colors are to be retrieved",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
     *                         @OA\Property(property="Name", type="string", example="Red"),
     *                         @OA\Property(property="Quantity", type="number", format="float", example=100)
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=5),
     *                 @OA\Property(property="total", type="integer", example=100)
     *             )
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/catalogs/{catalogId}/colors/update",
     *     summary="Update colors for a catalog",
     *     description="Updates the quantities of multiple colors for a specific catalog by its ID",
     *   *      tags={"Colors"},
     *     @OA\Parameter(
     *         name="catalogId",
     *         in="path",
     *         description="ID of the catalog for which colors are to be updated",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"data"},
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"Id", "Quantity"},
     *                     @OA\Property(property="Id", type="integer", example=1),
     *                     @OA\Property(property="Quantity", type="integer", example=50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Colors updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Colors updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="Id", type="integer", example=1),
     *                     @OA\Property(property="Name", type="string", example="Red"),
     *                     @OA\Property(property="Quantity", type="integer", example=150)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Catalog not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Catalog not found")
     *         )
     *     )
     * )
     */
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
