<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * GET /api/customer/orders
     *
     * Get customer's order history.
     */
    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with($this->orderRelations())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * POST /api/customer/orders
     *
     * Create an order from the customer's cart.
     */
    
        public function store(Request $request)
{
    $validated = $request->validate([
        'delivery_address' => [
            'required',
            'string',
            'max:500',
        ],

        'delivery_latitude' => [
            'nullable',
            'numeric',
        ],

        'delivery_longitude' => [
            'nullable',
            'numeric',
        ],

        'delivery_method' => [
            'required',
            'string',
            'in:standard,express',
        ],

        'payment_method' => [
            'required',
            'string',
            'max:50',
        ],

        'notes' => [
            'nullable',
            'string',
            'max:1000',
        ],
    ]);

    $user = $request->user();

    $order = DB::transaction(function () use ($user, $validated) {

        // ============================================================
        // GET CUSTOMER CART
        // ============================================================

        $cart = $user->cart()
            ->with([
                'items.product.farm',
            ])
            ->first();

        if (!$cart) {
            abort(422, 'No cart found for this customer.');
        }

        $cartItems = $cart->items;

        if ($cartItems->isEmpty()) {
            abort(422, 'Your cart has no items.');
        }

        // ============================================================
        // VALIDATE PRODUCTS + STOCK
        // ============================================================

        foreach ($cartItems as $cartItem) {

            $product = $cartItem->product;

            if (!$product) {
                abort(
                    422,
                    'One of the products in your cart no longer exists.'
                );
            }

            if (!$product->is_active) {
                abort(
                    422,
                    "Product {$product->name} is no longer available."
                );
            }

            if ($product->quantity_available < $cartItem->quantity) {
                abort(
                    422,
                    "Not enough stock for {$product->name}."
                );
            }
        }

        // ============================================================
        // CALCULATE SUBTOTAL
        // ============================================================

        $subtotal = 0;

        foreach ($cartItems as $cartItem) {

            $product = $cartItem->product;

            $subtotal +=
                (float) $product->price
                * (int) $cartItem->quantity;
        }

        // ============================================================
        // DELIVERY FEE
        // ============================================================

        $deliveryFee = 0;

        if ($validated['delivery_method'] === 'express') {
            $deliveryFee = 3.00;
        }

        $totalAmount = $subtotal + $deliveryFee;

        // ============================================================
        // CREATE ORDER
        // ============================================================

        $order = Order::create([
            'user_id' => $user->id,

            'status' => 'pending',

            'delivery_address' =>
                $validated['delivery_address'],

            'delivery_latitude' =>
                $validated['delivery_latitude'] ?? null,

            'delivery_longitude' =>
                $validated['delivery_longitude'] ?? null,

            'delivery_method' =>
                $validated['delivery_method'],

            'payment_method' =>
                $validated['payment_method'],

            'notes' =>
                $validated['notes'] ?? null,

            'total_amount' => $totalAmount,
        ]);

        // ============================================================
        // CREATE ORDER ITEMS
        // ============================================================

        foreach ($cartItems as $cartItem) {

            $product = $cartItem->product;

            $itemTotal =
                (float) $product->price
                * (int) $cartItem->quantity;

            $order->items()->create([
                'product_id' => $product->id,

                'quantity' =>
                    $cartItem->quantity,

                'price' =>
                    $product->price,

                'subtotal' =>
                    $itemTotal,
            ]);

            // Reduce inventory
            $product->decrement(
                'quantity_available',
                $cartItem->quantity
            );
        }

        // ============================================================
        // CREATE PAYMENT
        // ============================================================

        $order->payment()->create([
            'amount' => $totalAmount,

            'method' =>
                $validated['payment_method'],

            'status' => 'pending',
        ]);

        // ============================================================
        // CLEAR CART
        // ============================================================

        $cart->items()->delete();

        return $order;
    });

    // ================================================================
    // LOAD RELATIONSHIPS
    // ================================================================

    $order->load($this->orderRelations());

    return response()->json([
        'success' => true,

        'message' =>
            'Order created successfully.',

        'order' => $order,
    ], 201);
}
    /**
     * GET /api/customer/orders/{order}
     *
     * Get one customer order.
     */
    public function show(
        Request $request,
        Order $order
    ) {
        abort_unless(
            $order->user_id === $request->user()->id,
            403,
            'You are not authorized to view this order.'
        );

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * POST /api/customer/orders/{order}/cancel
     *
     * Cancel customer's order.
     */
    public function cancel(
        Request $request,
        Order $order
    ) {
        abort_unless(
            $order->user_id === $request->user()->id,
            403,
            'You are not authorized to cancel this order.'
        );

        if (!in_array(
            $order->status,
            ['pending', 'confirmed'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This order can no longer be cancelled.',
            ], 422);
        }

        DB::transaction(function () use ($order) {

            /*
             * Restore stock.
             */
            foreach ($order->items as $item) {

                $product = $item->product;

                if ($product) {
                    $product->increment(
                        'quantity_available',
                        $item->quantity
                    );
                }
            }

            /*
             * Cancel order.
             */
            $order->update([
                'status' => 'cancelled',
            ]);

            /*
             * Cancel payment.
             */
            if ($order->payment) {
                $order->payment->update([
                    'status' => 'cancelled',
                ]);
            }
        });

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'message' =>
                'Order cancelled successfully.',
            'order' => $order,
        ]);
    }

    /**
     * Relationships returned to Flutter.
     */
    private function orderRelations(): array
    {
        return [
            'items.product:id,farm_id,name,price,unit',
            'items.product.farm:id,farm_name',
            'payment',
        ];
    }
}