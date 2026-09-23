<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $cart->load('items.product.farm');

        return response()->json([
            'message' => 'Cart retrieved succesfully.',
            'cart' => $cart,
        ], 200);
    }

    public function addItem(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        $data = $request->validate([
            'product_id' => ['required','integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        if (! $product->is_active) {
            return response()->json([
            'message' => 'This product is not currently available.'
        ], 422);
        }

        $existing = $cart->items()->where('product_id', $product->id)->first();
        $newQuantity = $existing ? $existing->quantity + $data['quantity'] : $data['quantity'];

        if ($newQuantity > $product->quantity_available) {
             return response()->json([
            'message' => 'Not enough stock available.',
            'available' => $product->quantity_available,
            'requested' => $newQuantity,
        ], 422);
        }

        // Update existing cart item
        if ($existing) {
            $existing->update(['quantity' => $newQuantity, 'price' => $product->price]);
            $item = $existing;
        } else {
            // Create new cart item
            $item = $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'price' => $product->price,
            ]);
        }

        return response()->json([
        'message' => 'Product added to cart successfully.',
        'item' => $item->load('product'),
    ], 201);
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $cart = $this->getOrCreateCart($request);
        $this->authorizeItem($cart, $item);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($data['quantity'] > $item->product->quantity_available) {
            throw new HttpException(422, 'Not enough stock available for that quantity.');
        }

        $item->update($data);

        return $item->load('product');
    }

    public function removeItem(Request $request, CartItem $item)
    {
        $cart = $this->getOrCreateCart($request);
        $this->authorizeItem($cart, $item);
        $item->delete();

        return response()->json(null, 204);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json(null, 204);
    }

    private function getOrCreateCart(Request $request)
    {
        return $request->user()->cart ?? $request->user()->cart()->create();
    }

    private function authorizeItem($cart, CartItem $item): void
    {
        if ($item->cart_id !== $cart->id) {
            throw new HttpException(404, 'Item not found in your cart.');
        }
    }
}