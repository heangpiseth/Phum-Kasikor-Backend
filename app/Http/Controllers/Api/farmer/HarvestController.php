<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\HarvestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HarvestController extends Controller
{
    // ============================================================
    // LIST HARVESTS
    // ============================================================

    public function index(Request $request): JsonResponse
    {
        $farm = $this->getFarmerFarm();

        $query = HarvestLog::query()
            ->where('farm_id', $farm->id)
            ->with([
                'crop',
                'field',
            ])
            ->latest('harvest_date')
            ->latest('id');

        if ($request->filled('crop_id')) {
            $query->where(
                'crop_id',
                $request->input('crop_id')
            );
        }

        $harvests = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Harvest logs retrieved successfully.',
            'data' => $harvests,
        ]);
    }

    // ============================================================
    // CREATE HARVEST
    // ============================================================

    public function store(Request $request): JsonResponse
    {
        $farm = $this->getFarmerFarm();

        $validated = $request->validate([
            'crop_id' => [
                'required',
                'integer',
                'exists:crops,id',
            ],

            'harvest_date' => [
                'required',
                'date',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'quality' => [
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'poor',
                ]),
            ],

            'condition' => [
                'required',
                Rule::in([
                    'fresh',
                    'good',
                    'damaged',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        // --------------------------------------------------------
        // Make sure crop belongs to this farmer's farm
        // --------------------------------------------------------

        $crop = Crop::query()
            ->where('id', $validated['crop_id'])
            ->where('farm_id', $farm->id)
            ->with('field')
            ->first();

        if (!$crop) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The selected crop does not belong to your farm.',
            ], 422);
        }

        // --------------------------------------------------------
        // Create harvest
        // --------------------------------------------------------

        $harvest = DB::transaction(function () use (
            $validated,
            $farm,
            $crop
        ) {
            return HarvestLog::create([
                'farm_id' => $farm->id,
                'field_id' => $crop->field_id,
                'crop_id' => $crop->id,
                'harvest_date' => $validated['harvest_date'],
                'quantity' => $validated['quantity'],
                'quality' => $validated['quality'],
                'condition' => $validated['condition'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $harvest->load([
            'crop',
            'field',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Harvest recorded successfully.',
            'data' => $harvest,
        ], 201);
    }

    // ============================================================
    // SHOW HARVEST
    // ============================================================

    public function show($harvestId): JsonResponse
    {
        $farm = $this->getFarmerFarm();

        $harvest = HarvestLog::query()
            ->where('farm_id', $farm->id)
            ->where('id', $harvestId)
            ->with([
                'crop',
                'field',
            ])
            ->first();

        if (!$harvest) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Harvest record retrieved successfully.',
            'data' => $harvest,
        ]);
    }

    // ============================================================
    // UPDATE HARVEST
    // ============================================================

    public function update(
        Request $request,
        $harvestId
    ): JsonResponse {
        $farm = $this->getFarmerFarm();

        $harvest = HarvestLog::query()
            ->where('farm_id', $farm->id)
            ->where('id', $harvestId)
            ->first();

        if (!$harvest) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest record not found.',
            ], 404);
        }

        $validated = $request->validate([
            'harvest_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'quantity' => [
                'sometimes',
                'required',
                'numeric',
                'gt:0',
            ],

            'quality' => [
                'sometimes',
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'poor',
                ]),
            ],

            'condition' => [
                'sometimes',
                'required',
                Rule::in([
                    'fresh',
                    'good',
                    'damaged',
                ]),
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ]);

        $harvest->update($validated);

        $harvest->load([
            'crop',
            'field',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Harvest record updated successfully.',
            'data' => $harvest,
        ]);
    }

    // ============================================================
    // DELETE HARVEST
    // ============================================================

    public function destroy($harvestId): JsonResponse
    {
        $farm = $this->getFarmerFarm();

        $harvest = HarvestLog::query()
            ->where('farm_id', $farm->id)
            ->where('id', $harvestId)
            ->first();

        if (!$harvest) {
            return response()->json([
                'success' => false,
                'message' => 'Harvest record not found.',
            ], 404);
        }

        $harvest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Harvest record deleted successfully.',
        ]);
    }

    // ============================================================
    // GET FARM BELONGING TO CURRENT FARMER
    // ============================================================

    private function getFarmerFarm(): Farm
    {
        $farm = Farm::query()
            ->where('user_id', Auth::id())
            ->first();

        if (!$farm) {
            abort(404, 'You do not have a farm yet.');
        }

        return $farm;
    }
}