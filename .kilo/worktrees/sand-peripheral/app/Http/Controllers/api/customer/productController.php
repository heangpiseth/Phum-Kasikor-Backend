<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function categories()
    {
        return Category::with('children')->whereNull('parent_id')->get();
    }

    public function index(Request $request)
    {
        $query = Product::query()->where('is_active', true)->with('farm', 'category', 'images');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return $query->latest()->paginate(20);
    }

    public function show(Product $product)
    {
        return $product->load('farm', 'category', 'images');
    }

    public function reviews(Product $product)
    {
        return Review::where('product_id', $product->id)
            ->with('user:id,name,profile_image')
            ->latest()
            ->get();
    }
}