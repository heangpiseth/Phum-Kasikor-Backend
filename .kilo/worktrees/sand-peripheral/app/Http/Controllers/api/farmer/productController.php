<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Farm;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductController extends Controller
{
    // ---- Categories (shared reference data across all farmers) ----

    public function categories()
    {
        return Category::with('children')->whereNull('parent_id')->get();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'image' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    // ---- Products, scoped to one of the farmer's farms ----

    public function index(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        return $farm->products()->with('category', 'images')->get();
    }

    public function store(Request $request, Farm $farm)
    {
        //validate data sent by farmer
        $validated = $request->validate([
            'farm_id' => ['required', 'interger', 'exists:farms,id'],
            'category_id' => ['required', 'interger', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'quantity_available' => ['required', 'integer', 'min:0'],
        ]);

        $farmer = $request->user();

        $farm = Farm::where('id', $validated['farm_id'])->where('user_id', $farmer->id)->first();

        if (!$farm) {
            return response()->json([
                'message' => 'You do not own this farm.'
            ], 403);
        }

        $product = Product::create([
            'farm_id' => $farm->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'quantity_available' => $validated['quantity_available'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function show(Request $request, Farm $farm, Product $product)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        return $product->load('category', 'images');
    }

    public function update(Request $request, Farm $farm, Product $product)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'quantity_available' => ['sometimes', 'numeric', 'min:0'],
            'harvest_date' => ['nullable', 'date'],
            'farming_method' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($data);

        return $product;
    }

    public function destroy(Request $request, Farm $farm, Product $product)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);
        $product->delete();

        return response()->json(null, 204);
    }

    // ---- Product images ----

    public function storeImage(Request $request, Farm $farm, Product $product)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        $data = $request->validate([
            'image' => ['required', 'string', 'max:500'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['is_primary'])) {
            $product->images()->update(['is_primary' => false]);
        }

        $image = $product->images()->create($data);

        return response()->json($image, 201);
    }

    public function destroyImage(Request $request, Farm $farm, Product $product, ProductImage $image)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        if ($image->product_id !== $product->id) {
            throw new HttpException(404, 'Image not found on this product.');
        }

        $image->delete();

        return response()->json(null, 204);
    }

    // ---- Ownership guards ----

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        if ($farm->user_id !== $request->user()->id) {
            throw new HttpException(403, 'This farm does not belong to you.');
        }
    }

    private function authorizeProduct(Farm $farm, Product $product): void
    {
        if ($product->farm_id !== $farm->id) {
            throw new HttpException(404, 'Product not found on this farm.');
        }
    }
}
