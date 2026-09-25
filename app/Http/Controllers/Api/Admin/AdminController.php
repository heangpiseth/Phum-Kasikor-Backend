<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerVerification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'metrics' => [
                'farmers' => User::where('role', 'farmer')->count(),
                'pending_verifications' => FarmerVerification::where('status', 'pending')->count(),
                'products' => Product::count(),
                'pending_products' => Product::where('approval_status', 'pending')->count(),
                'orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'paid_revenue' => (float) Order::whereHas('payment', fn ($query) => $query->where('status', 'paid'))->sum('total_amount'),
            ],
            'recent_orders' => Order::with(['user:id,name,email', 'payment'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }

    public function farmers(Request $request)
    {
        $farmers = User::query()
            ->where('role', 'farmer')
            ->with(['farms:id,user_id,farm_name,location', 'farmerVerification:id,user_id,status,submitted_at'])
            ->withCount('farms')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json(['success' => true, 'farmers' => $farmers]);
    }

    public function verifications(Request $request)
    {
        $verifications = FarmerVerification::query()
            ->with(['user:id,name,email,phone'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('submitted_at')
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json(['success' => true, 'verifications' => $verifications]);
    }

    public function verificationDocument(FarmerVerification $verification, string $side): StreamedResponse
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);

        $path = $side === 'front' ? $verification->front_image_path : $verification->back_image_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function reviewVerification(Request $request, FarmerVerification $verification)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        if ($verification->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending verifications can be reviewed.']);
        }

        $verification->update([
            'status' => $data['decision'],
            'rejection_reason' => $data['decision'] === 'rejected' ? $data['rejection_reason'] : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'verification' => $verification->fresh()->load('user:id,name,email')]);
    }

    public function products(Request $request)
    {
        $products = Product::query()
            ->with(['farm:id,user_id,farm_name', 'farm.user:id,name,email', 'category:id,name', 'images'])
            ->when($request->filled('status'), fn ($query) => $query->where('approval_status', $request->string('status')->toString()))
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json(['success' => true, 'products' => $products]);
    }

    public function reviewProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        if ($product->approval_status !== 'pending') {
            throw ValidationException::withMessages(['approval_status' => 'Only pending products can be reviewed.']);
        }

        $approved = $data['decision'] === 'approved';
        $product->update([
            'approval_status' => $data['decision'],
            'rejection_reason' => $approved ? null : $data['rejection_reason'],
            'approved_by' => $approved ? $request->user()->id : null,
            'approved_at' => $approved ? now() : null,
            'is_active' => $approved,
        ]);

        return response()->json(['success' => true, 'product' => $product->fresh()->load(['farm', 'category', 'images'])]);
    }
}
