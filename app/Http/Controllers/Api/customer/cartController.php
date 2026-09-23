<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CartController extends Controller
{
    /**
     * GET /api/customer/cart
     */
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $cart->load([
            'items.product.farm',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully.',
            'cart' => $cart,
        ], 200);
    }

    /**
     * POST /api/customer/cart/items
     */
    public function addItem(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $data = $request->validate([
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);

        $product = Product::findOrFail(
            $data['product_id']
        );

        // Product must be active
        if (!$product->is_active) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This product is not currently available.',
            ], 422);
        }

        // ============================================================
        // CHECK STOCK
        // ============================================================

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = $existing
            ? (float) $existing->quantity
                + (float) $data['quantity']
            : (float) $data['quantity'];

        if ($newQuantity > $product->quantity_available) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Not enough stock available.',
                'available' =>
                    $product->quantity_available,
                'requested' =>
                    $newQuantity,
            ], 422);
        }

        // ============================================================
        // CREATE / UPDATE CART ITEM
        // ============================================================

        if ($existing) {

            $existing->update([
                'quantity' => $newQuantity,
                'price' => $product->price,
            ]);

            $item = $existing;

        } else {

            $item = $cart->items()->create([
                'product_id' =>
                    $product->id,

                'quantity' =>
                    $data['quantity'],

                'price' =>
                    $product->price,
            ]);
        }

        $item->load([
            'product.farm',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Product added to cart successfully.',
            'item' => $item,
        ], 201);
    }

    /**
     * PUT /api/customer/cart/items/{item}
     */
    public function updateItem(
        Request $request,
        CartItem $item
    ) {
        $cart = $this->getOrCreateCart($request);

        $this->authorizeItem($cart, $item);

        $data = $request->validate([
            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);

        if (
            $data['quantity']
            > $item->product->quantity_available
        ) {
            throw new HttpException(
                422,
                'Not enough stock available for that quantity.'
            );
        }

        $item->update([
            'quantity' => $data['quantity'],
            'price' => $item->product->price,
        ]);

        $item->load([
            'product.farm',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Cart item updated successfully.',
            'item' => $item,
        ]);
    }

    /**
     * DELETE /api/customer/cart/items/{item}
     */
    public function removeItem(
        Request $request,
        CartItem $item
    ) {
        $cart = $this->getOrCreateCart($request);

        $this->authorizeItem($cart, $item);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Cart item removed successfully.',
        ], 200);
    }

    /**
     * DELETE /api/customer/cart
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Cart cleared successfully.',
        ], 200);
    }

    /**
     * Get the authenticated customer's cart.
     *
     * IMPORTANT:
     * We always use the authenticated user.
     */
    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();

        if (!$user) {
            throw new HttpException(
                401,
                'Unauthenticated.'
            );
        }

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
        ]);

        return $cart;
    }

    /**
     * Make sure the cart item belongs to this user's cart.
     */
    private function authorizeItem(
        Cart $cart,
        CartItem $item
    ): void {
        if ($item->cart_id !== $cart->id) {
            throw new HttpException(
                404,
                'Item not found in your cart.'
            );
        }
    }
}