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
        // Make sure the authenticated farmer owns this farm.
        $this->authorizeFarm($request, $farm);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'unit' => ['required', 'string', 'max:30'],
            'quantity_available' => ['required', 'numeric', 'min:0'],
            'harvest_date' => ['nullable', 'date'],
            'farming_method' => ['nullable', 'string', 'max:100'],
        ]);

        $product = Product::create([
            'farm_id' => $farm->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'quantity_available' => $validated['quantity_available'],
            'harvest_date' => $validated['harvest_date'] ?? null,
            'farming_method' => $validated['farming_method'] ?? null,
            'is_active' => false,
            'approval_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load('category', 'images'),
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
        ]);

        $product->update(array_merge($data, [
            'is_active' => false,
            'approval_status' => 'pending',
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]));

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

    public function storeImage(Request $request, $farm, $product)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $productModel = Product::with('farm')->findOrFail($product);

        // Make sure the product belongs to the farm in the URL
        if ((int) $productModel->farm_id !== (int) $farm) {
            return response()->json([
                'message' => 'Product does not belong to this farm.',
            ], 422);
        }

        // Make sure the authenticated farmer owns the farm
        if ((int) $productModel->farm->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to upload images for this product.',
            ], 403);
        }

        // Permanently store the image
        $path = $request->file('image')->store('products', 'public');

        $image = $productModel->images()->create([
            'image' => $path,
            'is_primary' => $productModel->images()->count() === 0,
        ]);

        return response()->json($image, 201);
    }

    public function destroyImage(
        Request $request,
        Farm $farm,
        Product $product,
        ProductImage $image
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        if ($image->product_id !== $product->id) {
            throw new HttpException(
                404,
                'Image not found on this product.'
            );
        }

        $wasPrimary = $image->is_primary;

        $image->delete();

        if ($wasPrimary) {
            $nextImage = $product->images()
                ->orderBy('id')
                ->first();

            if ($nextImage) {
                $nextImage->update([
                    'is_primary' => true,
                ]);
            }
        }

        return response()->json(null, 204);
    }

    public function setPrimaryImage(
        Request $request,
        Farm $farm,
        Product $product,
        ProductImage $image
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeProduct($farm, $product);

        if ($image->product_id !== $product->id) {
            throw new HttpException(
                404,
                'Image not found on this product.'
            );
        }

        // Remove primary status from all other images.
        $product->images()->update([
            'is_primary' => false,
        ]);

        // Make this image the primary image.
        $image->update([
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'Primary image updated successfully.',
            'image' => $image->fresh(),
        ]);
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
