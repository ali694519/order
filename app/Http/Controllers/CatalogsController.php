<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\Catalog;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;



class CatalogsController extends Controller
{

  /**
   * @OA\Get(
   *     path="/api/catalogs",
   *     summary="Get a list of catalogs with optional filters",
   *     description="Returns a paginated list of catalogs with their total meters and optional filters for name and price",
   *     tags={"Catalogs"},
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
   *         description="Filter catalogs by attributes (e.g., 'Name' or 'Price')",
   *         required=false,
   *         @OA\Schema(
   *             type="array",
   *             @OA\Items(
   *                 type="string",
   *                 enum={"Name", "Price"}
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful operation",
   *         @OA\JsonContent(
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="current_page", type="integer", example=1),
   *                 @OA\Property(property="per_page", type="integer", example=5),
   *                 @OA\Property(property="total", type="integer", example=100),
   *                 @OA\Property(property="last_page", type="integer", example=20),
   *                 @OA\Property(property="data", type="array",
   *                     @OA\Items(
   *                         @OA\Property(property="Id", type="integer", example=1),
   *                         @OA\Property(property="Name", type="string", example="Catalog Name"),
   *                         @OA\Property(property="Price", type="number", format="float", example=99.99),
   *                         @OA\Property(property="total_meters", type="number", format="float", example=500.00)
   *                     )
   *                 )
   *             )
   *         )
   *     )
   * )
   */
  public function get(Request $request)
  {
    $perPage = $request->input('per_page', 5);
    $page = $request->input('page', 1);

    $catalogs = QueryBuilder::for(Catalog::class)
      ->allowedFilters(['Name', 'Price'])->with('quantities')
      ->withSum('quantities as total_meters', 'Quantity')
      ->paginate($perPage, ['*'], 'page', $page);
    return response()->json([
      'data' => $catalogs
    ]);
  }
  /**
   * @OA\Post(
   *     path="/api/catalogs",
   *     summary="Create a new catalog",
   *     description="Creates a new catalog with a name and price",
   *  *     tags={"Catalogs"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"Name", "Price"},
   *             @OA\Property(property="Name", type="string", example="New Catalog"),
   *             @OA\Property(property="Price", type="number", format="float", example=50.00)
   *         )
   *     ),
   *     @OA\Response(
   *         response=201,
   *         description="Catalog created successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Catalog created successfully!"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     )
   * )
   */
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
  /**
   * @OA\Put(
   *     path="/api/catalogs/{catalog}",
   *     summary="Update an existing catalog",
   *     description="Updates a catalog's name or price",
   *  *     tags={"Catalogs"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="catalog",
   *         in="path",
   *         description="ID of the catalog to update",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\RequestBody(
   *         required=false,
   *         @OA\JsonContent(
   *             @OA\Property(property="Name", type="string", example="Updated Catalog"),
   *             @OA\Property(property="Price", type="number", format="float", example=60.00)
   *         )
   *     ),
   *      @OA\Response(
   *         response=200,
   *         description="Catalog updated successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Catalog updated successfully!"),
   *             @OA\Property(property="data", type="object",
   *                 @OA\Property(property="Id", type="integer", example=1),
   *                 @OA\Property(property="Name", type="string", example="Updated Catalog"),
   *                 @OA\Property(property="Price", type="number", format="float", example=60.00)
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Catalog not found",
   *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Catalog not found"))
   *     )
   * )
   */
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
  /**
   * @OA\Get(
   *     path="/api/catalogs/{catalog}",
   *     summary="Get a catalog by ID",
   *     description="Returns a specific catalog by its ID along with its colors",
   *     tags={"Catalogs"},
   *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="catalog",
   *         in="path",
   *         description="ID of the catalog to retrieve",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Successful operation",
   *         @OA\JsonContent(
   *             @OA\Property(
   *                 property="data",
   *                 @OA\Property(
   *                     property="catalog",
   *                     type="object",
   *                     @OA\Property(property="Id", type="integer", example=1),
   *                     @OA\Property(property="Name", type="string", example="Sample Catalog"),
   *                     @OA\Property(property="Price", type="number", format="float", example=99.99)
   *                 ),
   *                 @OA\Property(
   *                     property="colors",
   *                     type="array",
   *                     @OA\Items(
   *                         type="object",
   *                         @OA\Property(property="Id", type="integer", example=1),
   *                         @OA\Property(property="Name", type="string", example="Red"),
   *                         @OA\Property(property="Quantity", type="integer", example=10),
   *                         @OA\Property(property="CatalogId", type="integer", example=1)
   *                     )
   *                 )
   *             )
   *         )
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Catalog not found",
   *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Catalog not found"))
   *     )
   * )
   */
  public function show($catalog)
  {
    $catalog = Catalog::where('Id', $catalog)
      ->first();
    if (!$catalog) {
      return response()->json(['message' => 'Catalog not found'], 404);
    }
    $colors = Color::where('CatalogId', $catalog->Id)->get();

    return response()->json([
      'data' => [
        'catalog' => $catalog,
        'colors' => $colors
      ]
    ]);
  }
  /**
   * @OA\Delete(
   *     path="/api/catalogs/{catalog}",
   *     summary="Delete a catalog",
   *  *     tags={"Catalogs"},
   *  *     security={{"bearerAuth": {}}},
   *     @OA\Parameter(
   *         name="catalog",
   *         in="path",
   *         description="ID of the catalog to delete",
   *         required=true,
   *         @OA\Schema(type="integer")
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="Catalog deleted successfully",
   *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Catalog deleted successfully!"))
   *     ),
   *     @OA\Response(
   *         response=404,
   *         description="Catalog not found",
   *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Catalog not found"))
   *     )
   * )
   */

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
