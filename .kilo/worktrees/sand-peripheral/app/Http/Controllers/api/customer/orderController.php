<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->orders()->with('items.product')->latest()->get();
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return $order->load('items.product.farm', 'payment');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'delivery_address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'delivery_method' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $cart = $request->user()->cart;

        if (! $cart || $cart->items()->count() === 0) {
            throw new HttpException(422, 'Your cart is empty.');
        }

        $order = DB::transaction(function () use ($request, $data, $cart) {
            $cart->load('items.product');

            // Re-check stock for every item before committing to the order.
            foreach ($cart->items as $cartItem) {

                $product = Product::where('id', $cartItem->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new HttpException(
                        422,
                        'One of the products in your cart no longer exists.'
                    );
                }

                // Product is no longer available
                if (!$product->is_active) {
                    throw new HttpException(
                        422,
                        "{$product->name} is no longer available"
                    );
                }

                if ($cartItem->quantity > $product->quantity_available) {
                    throw new HttpException(
                        422,
                        "Not enough stock for {$product->name}."
                    );
                }
                $cartItem->setRelation('product', $product);
            }

            // Get the latest product prices.
            foreach ($cart->items as $cartItem) {
                $cartItem->update([
                    'price' => $cartItem->product->price,
                ]);
            }
            
            // Calculate total
            $totalAmount = $cart->items->sum(
                fn($item) => $item->price * $item->quantity
            );

            $order = $request->user()->orders()->create([
                ...$data,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($cart->items as $cartItem) {
                $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                ]);

                $cartItem->product->decrement('quantity_available', $cartItem->quantity);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            $cart->items()->delete();

            return $order;
        });

        return response()->json($order->load('items.product', 'payment'), 201);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if (! in_array($order->status, ['pending', 'confirmed'])) {
            throw new HttpException(422, 'This order can no longer be cancelled.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $item->product->increment('quantity_available', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });

        return $order;
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            throw new HttpException(403, 'This order does not belong to you.');
        }
    }
}
