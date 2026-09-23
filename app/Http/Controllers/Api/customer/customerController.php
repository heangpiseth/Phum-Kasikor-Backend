<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmFavorite;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Review;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerController extends Controller
{
    // ---- Favorites (products) ----

    public function favorites(Request $request)
    {
        return $request->user()->favorites()->with('product.farm')->get();
    }

    public function storeFavorite(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $data['product_id'],
        ]);

        return response()->json($favorite, 201);
    }

    public function destroyFavorite(Request $request, Product $product)
    {
        $request->user()->favorites()->where('product_id', $product->id)->delete();

        return response()->json(null, 204);
    }

    // ---- Farm favorites ----

    public function farmFavorites(Request $request)
    {
        return $request->user()->farmFavorites()->with('farm')->get();
    }

    public function storeFarmFavorite(Request $request)
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
        ]);

        $favorite = FarmFavorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'farm_id' => $data['farm_id'],
        ]);

        return response()->json($favorite, 201);
    }

    public function destroyFarmFavorite(Request $request, Farm $farm)
    {
        $request->user()->farmFavorites()->where('farm_id', $farm->id)->delete();

        return response()->json(null, 204);
    }

    // ---- Reviews ----

    public function storeReview(Request $request)
    {
        $data = $request->validate([
            'farm_id' => ['nullable', 'exists:farms,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        if (empty($data['farm_id']) && empty($data['product_id'])) {
            throw new HttpException(422, 'A review must be for either a farm or a product.');
        }

        $review = $request->user()->reviews()->create($data);

        return response()->json($review, 201);
    }

    public function updateReview(Request $request, Review $review)
    {
        $this->authorizeReview($request, $review);

        $data = $request->validate([
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $review->update($data);

        return $review;
    }

    public function destroyReview(Request $request, Review $review)
    {
        $this->authorizeReview($request, $review);
        $review->delete();

        return response()->json(null, 204);
    }

    // ---- Subscriptions ----

    public function subscriptions(Request $request)
    {
        return $request->user()->subscriptions()->with('farm')->get();
    }

    public function storeSubscription(Request $request)
    {
        $data = $request->validate([
            'farm_id' => ['required', 'exists:farms,id'],
            'type' => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:50'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'next_delivery_date' => ['nullable', 'date'],
        ]);

        $subscription = $request->user()->subscriptions()->create([
            ...$data,
            'status' => 'active',
        ]);

        return response()->json($subscription, 201);
    }

    public function updateSubscription(Request $request, Subscription $subscription)
    {
        $this->authorizeSubscription($request, $subscription);

        $data = $request->validate([
            'status' => ['sometimes', 'in:active,paused,cancelled'],
            'frequency' => ['sometimes', 'string', 'max:50'],
            'next_delivery_date' => ['nullable', 'date'],
        ]);

        $subscription->update($data);

        return $subscription;
    }

    private function authorizeReview(Request $request, Review $review): void
    {
        if ($review->user_id !== $request->user()->id) {
            throw new HttpException(403, 'This review does not belong to you.');
        }
    }

    private function authorizeSubscription(Request $request, Subscription $subscription): void
    {
        if ($subscription->user_id !== $request->user()->id) {
            throw new HttpException(403, 'This subscription does not belong to you.');
        }
    }
}