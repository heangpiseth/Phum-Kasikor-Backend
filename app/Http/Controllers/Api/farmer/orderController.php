<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * GET /api/farmer/orders
     */
    public function index(Request $request)
    {
        $farmIds = $request->user()
            ->farms()
            ->pluck('id');

        $orders = Order::query()
            ->whereHas('items.product', function ($query) use ($farmIds) {
                $query->whereIn('farm_id', $farmIds);
            })
            ->with($this->orderRelations())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * GET /api/farmer/orders/{order}
     */
    public function show(
        Request $request,
        Order $order
    ) {
        $this->authorizeOrder($request, $order);

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * PUT /api/farmer/orders/{order}/confirm
     */
    public function confirm(
        Request $request,
        Order $order
    ) {
        $this->authorizeOrder($request, $order);

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending orders can be confirmed.',
            ], 422);
        }

        $order->update([
            'status' => 'confirmed',
        ]);

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'message' => 'Order confirmed successfully.',
            'order' => $order,
        ]);
    }

    /**
     * PUT /api/farmer/orders/{order}
     */
    public function updateStatus(
        Request $request,
        Order $order
    ) {
        $this->authorizeOrder($request, $order);

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:confirmed,preparing,ready,out_for_delivery,delivered,cancelled',
            ],
        ]);

        $currentStatus = $order->status;
        $newStatus = $validated['status'];

        if (!$this->canChangeStatus($currentStatus, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' =>
                    "Cannot change order status from {$currentStatus} to {$newStatus}.",
            ], 422);
        }

        $order->update([
            'status' => $newStatus,
        ]);

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'order' => $order,
        ]);
    }

    /**
     * PUT /api/farmer/orders/{order}/mark-paid
     */
    public function markPaymentPaid(
        Request $request,
        Order $order
    ) {
        $this->authorizeOrder($request, $order);

        $payment = $order->payment;

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.',
            ], 404);
        }

        $payment->update([
            'status' => 'paid',
        ]);

        $order->load($this->orderRelations());

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as paid.',
            'order' => $order,
        ]);
    }

    /**
     * GET /api/farmer/earnings
     */
    /**
 * GET /api/farmer/earnings
 */
public function earnings(Request $request)
{
    $farmIds = $request->user()
        ->farms()
        ->pluck('id');

    /*
     * Get only orders that contain products
     * belonging to this farmer's farms and
     * have actually been paid.
     */
    $paidOrders = Order::query()
        ->whereHas('items.product', function ($query) use ($farmIds) {
            $query->whereIn('farm_id', $farmIds);
        })
        ->whereHas('payment', function ($query) {
            $query->where('status', 'paid');
        })
        ->with([
            'items.product:id,farm_id',
            'payment:id,order_id,status,paid_at',
        ])
        ->get();

    /*
     * Store earnings by month.
     *
     * Example:
     * [
     *     '2026-06' => 120,
     *     '2026-08' => 250,
     * ]
     */
    $monthlyEarnings = [];

    foreach ($paidOrders as $order) {
        $payment = $order->payment;

        if (!$payment) {
            continue;
        }

        /*
         * Earnings should be grouped by the date
         * the payment was actually marked as paid.
         */
        $date = $payment->paid_at ?? $order->created_at;

        if (!$date) {
            continue;
        }

        $farmerAmount = 0;

        foreach ($order->items as $item) {
            $product = $item->product;

            if (!$product) {
                continue;
            }

            /*
             * Only count products belonging
             * to the authenticated farmer.
             */
            if (!$farmIds->contains($product->farm_id)) {
                continue;
            }

            /*
             * IMPORTANT:
             *
             * Use the price stored on order_items.
             *
             * This is the price the customer actually
             * paid at the time of purchase.
             */
            $price = (float) ($item->price ?? 0);

            $quantity = (float) ($item->quantity ?? 0);

            $farmerAmount += $price * $quantity;
        }

        if ($farmerAmount <= 0) {
            continue;
        }

        $month = $date->format('Y-m');

        if (!isset($monthlyEarnings[$month])) {
            $monthlyEarnings[$month] = 0;
        }

        $monthlyEarnings[$month] += $farmerAmount;
    }

    /*
     * ----------------------------------------------------------
     * CREATE CONTINUOUS MONTHLY DATA
     * ----------------------------------------------------------
     *
     * We return every month from the earliest earning
     * month through the current month.
     *
     * Months without earnings receive 0.
     */

    $monthly = collect();

    if (!empty($monthlyEarnings)) {
        ksort($monthlyEarnings);

        $firstMonth = \Carbon\Carbon::createFromFormat(
            'Y-m',
            array_key_first($monthlyEarnings)
        )->startOfMonth();

        $currentMonth = now()->startOfMonth();

        $cursor = $firstMonth->copy();

        while ($cursor <= $currentMonth) {
            $monthKey = $cursor->format('Y-m');

            $monthly->push([
                'month' => $monthKey,
                'amount' => round(
                    (float) ($monthlyEarnings[$monthKey] ?? 0),
                    2
                ),
            ]);

            $cursor->addMonth();
        }
    }

    /*
     * If the farmer has never earned anything,
     * still return the current month with 0.
     */
    if ($monthly->isEmpty()) {
        $monthly->push([
            'month' => now()->format('Y-m'),
            'amount' => 0,
        ]);
    }

    /*
     * Total earnings across all months.
     */
    $totalEarnings = $monthly->sum('amount');

    /*
     * Current month's earnings.
     */
    $currentMonthKey = now()->format('Y-m');

    $thisMonth = $monthly->firstWhere(
        'month',
        $currentMonthKey
    );

    return response()->json([
        'success' => true,

        'total_earnings' => round(
            (float) $totalEarnings,
            2
        ),

        'this_month' => round(
            (float) ($thisMonth['amount'] ?? 0),
            2
        ),

        'monthly_earnings' => $monthly->values(),
    ]);
}

    /**
     * Relationships needed by Flutter.
     */
    private function orderRelations(): array
    {
        return [
            'user:id,name,email,phone',
            'items.product:id,farm_id,name,price,unit',
            'items.product.farm:id,farm_name',
            'payment',
        ];
    }

    /**
     * Make sure the order belongs to
     * one of the authenticated farmer's farms.
     */
    private function authorizeOrder(
        Request $request,
        Order $order
    ): void {
        $farmIds = $request->user()
            ->farms()
            ->pluck('id');

        $belongsToFarmer = $order
            ->items()
            ->whereHas('product', function ($query) use ($farmIds) {
                $query->whereIn('farm_id', $farmIds);
            })
            ->exists();

        abort_unless(
            $belongsToFarmer,
            403,
            'You are not authorized to access this order.'
        );
    }

    /**
     * Valid order status transitions.
     */
    private function canChangeStatus(
        string $current,
        string $new
    ): bool {
        $allowed = [

            'pending' => [
                'confirmed',
                'cancelled',
            ],

            'confirmed' => [
                'preparing',
                'cancelled',
            ],

            'preparing' => [
                'ready',
                'cancelled',
            ],

            'ready' => [
                'out_for_delivery',
                'cancelled',
            ],

            'out_for_delivery' => [
                'delivered',
            ],

            'delivered' => [],

            'cancelled' => [],
        ];

        return in_array(
            $new,
            $allowed[$current] ?? [],
            true
        );
    }
}