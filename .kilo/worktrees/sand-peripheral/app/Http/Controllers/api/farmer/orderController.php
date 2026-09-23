<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    private const STATUSES = [
        'pending', 'confirmed', 'packed', 'dispatched',
        'out_for_delivery', 'delivered', 'cancelled',
    ];

    /**
     * Orders that contain at least one item from one of this farmer's products.
     */
    public function index(Request $request)
    {
         $farmerId = $request->user()->id;

        $orders = Order::whereHas('items.product.farm', function ($query) use ($farmerId) {
            $query->where('user_id', $farmerId);
        })
        ->with([
            'user',
            'items.product.farm',
        ])
        ->latest()
        ->get();

        return response()->json([
            'message' => 'Farmer orders retrieved successfully.',
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        $this->authorizeOrder($order, $farmIds);

        // Only show this farmer their own items within the order, not other farms' items.
        $order->load(['items.product' => function ($query) use ($farmIds) {
            $query->whereIn('farm_id', $farmIds);
        }]);

        return $order;
    }

    public function updateStatus(Request $request, Order $order)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        $this->authorizeOrder($order, $farmIds);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
        ]);

        $order->update($data);

        return $order;
    }

    private function authorizeOrder(Order $order, $farmIds): void
    {
        $farmerId = $request->user()->id;

    $ownsProduct = $order->items()
        ->whereHas('product.farm', function ($query) use ($farmerId) {
            $query->where('user_id', $farmerId);
        })
        ->exists();

    if (! $ownsProduct) {
        throw new HttpException(
            403,
            'You are not authorized to manage this order.'
        );
    }
    }
    public function markPaymentPaid(Request $request, Order $order)
    {
        $farmIds = $request->user()->farms()->pluck('id');
        $this->authorizeOrder($order, $farmIds);

        $payment = $order->payment();

        if(!$payment){
            throw new HttpException(404, 'No payment record found for this order.');
        }

        if ($payment->status === 'paid'){
            throw new HttpException(422, 'This payment has already been marked as paid.');
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $payment;
    }
    public function confirm(Request $request, Order $order)
    {
    $this->authorizeOrder($request, $order);

    if ($order->status !== 'pending') {
        throw new HttpException(
            422,
            'Only pending orders can be confirmed.'
        );
    }

    $order->update([
        'status' => 'confirmed',
    ]);

    return response()->json([
        'message' => 'Order confirmed successfully.',
        'order' => $order->load('items.product'),
    ]);
    }
}