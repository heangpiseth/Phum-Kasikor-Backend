<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\FarmerVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FarmerVerificationController extends Controller
{
    /**
     * Get the authenticated farmer's verification.
     */
    public function show(Request $request)
    {
        $verification = FarmerVerification::where(
            'user_id',
            $request->user()->id
        )->first();

        if (!$verification) {
            return response()->json([
                'verification' => null,
                'message' => 'Identity verification has not been submitted yet.',
            ]);
        }

        return response()->json([
            'verification' => $this->formatVerification($verification),
        ]);
    }

    /**
     * Submit National ID verification.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isFarmer()) {
            return response()->json([
                'message' => 'Only farmers can submit identity verification.',
            ], 403);
        }

        $existing = FarmerVerification::where(
            'user_id',
            $user->id
        )->first();

        /*
         * Prevent submitting another application while one
         * is already pending.
         */
        if ($existing && $existing->status === 'pending') {
            return response()->json([
                'message' => 'Your identity verification is already pending.',
                'verification' => $this->formatVerification($existing),
            ], 422);
        }

        /*
         * Do not allow resubmission after approval.
         */
        if ($existing && $existing->status === 'approved') {
            return response()->json([
                'message' => 'Your identity verification has already been approved.',
                'verification' => $this->formatVerification($existing),
            ], 422);
        }

        $data = $request->validate([
            'id_number' => [
                'required',
                'string',
                'max:100',
            ],

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'front_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'back_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
         * Store private identity documents.
         *
         * IMPORTANT:
         * We intentionally use the private "local" disk.
         * These files should not be publicly accessible.
         */
        $frontPath = $request->file('front_image')->store(
            'farmer-verifications/' . $user->id,
            'local'
        );

        $backPath = $request->file('back_image')->store(
            'farmer-verifications/' . $user->id,
            'local'
        );

        /*
         * Delete old rejected documents before replacing them.
         */
        if ($existing) {
            if ($existing->front_image_path) {
                Storage::disk('local')->delete(
                    $existing->front_image_path
                );
            }

            if ($existing->back_image_path) {
                Storage::disk('local')->delete(
                    $existing->back_image_path
                );
            }
        }

        /*
         * Reuse the existing rejected record instead of
         * creating multiple verification records.
         */
        $verification = $existing ?? new FarmerVerification();

        $verification->user_id = $user->id;
        $verification->id_number = $data['id_number'];
        $verification->full_name = $data['full_name'];
        $verification->front_image_path = $frontPath;
        $verification->back_image_path = $backPath;
        $verification->status = 'pending';
        $verification->rejection_reason = null;
        $verification->submitted_at = now();
        $verification->reviewed_at = null;
        $verification->reviewed_by = null;

        $verification->save();

        return response()->json([
            'message' => 'Identity verification submitted successfully.',
            'verification' => $this->formatVerification($verification),
        ], $existing ? 200 : 201);
    }

    /**
     * Allow a farmer to resubmit after rejection.
     */
    public function resubmit(Request $request)
    {
        $verification = FarmerVerification::where(
            'user_id',
            $request->user()->id
        )->first();

        if (!$verification) {
            return response()->json([
                'message' => 'No identity verification was found.',
            ], 404);
        }

        if ($verification->status !== 'rejected') {
            return response()->json([
                'message' => 'Only rejected verification can be resubmitted.',
            ], 422);
        }

        /*
         * We return the same endpoint requirements.
         * The Flutter app can submit again using POST.
         */
        return response()->json([
            'message' => 'You can now resubmit your identity verification.',
            'verification' => $this->formatVerification($verification),
        ]);
    }

    /**
     * Format verification data for Flutter.
     *
     * Do NOT return private file paths.
     */
    private function formatVerification(
        FarmerVerification $verification
    ): array {
        return [
            'id' => $verification->id,
            'user_id' => $verification->user_id,
            'id_number' => $verification->id_number,
            'full_name' => $verification->full_name,
            'status' => $verification->status,
            'rejection_reason' => $verification->rejection_reason,
            'submitted_at' => $verification->submitted_at?->toISOString(),
            'reviewed_at' => $verification->reviewed_at?->toISOString(),
        ];
    }
}