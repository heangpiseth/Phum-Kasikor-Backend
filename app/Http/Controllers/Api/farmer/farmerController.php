<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FieldModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FarmerController extends Controller
{
    // ============================================================
    // FARMS
    // ============================================================

    public function index(Request $request)
    {
        return $request->user()->farms;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'farm_size' => ['nullable', 'numeric'],
            'farming_method' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
        ]);

        $farm = $request->user()->farms()->create($data);

        return response()->json($farm, 201);
    }

    public function show(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        return $farm;
    }

    public function update(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        $data = $request->validate([
            'farm_name' => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'farm_size' => ['nullable', 'numeric'],
            'farming_method' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
        ]);

        $farm->update($data);

        return $farm;
    }

    public function destroy(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        $farm->delete();

        return response()->json(null, 204);
    }

    private function authorizeFarm(
        Request $request,
        Farm $farm
    ): void {
        if ($farm->user_id !== $request->user()->id) {
            throw new HttpException(
                403,
                'This farm does not belong to you.'
            );
        }
    }

    // ============================================================
    // FIELDS
    // ============================================================

    public function fields(
        Request $request,
        Farm $farm
    ) {
        $this->authorizeFarm($request, $farm);

        return $farm->fields;
    }

    public function storeField(
        Request $request,
        Farm $farm
    ) {
        $this->authorizeFarm($request, $farm);

        $data = $request->validate([
            'name' => ['required', 'string'],
            'area' => ['nullable', 'numeric'],
            'soil_type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $field = $farm->fields()->create($data);

        return response()->json($field, 201);
    }

    public function updateField(
        Request $request,
        Farm $farm,
        FieldModel $field
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeField($farm, $field);

        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'area' => ['nullable', 'numeric'],
            'soil_type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $field->update($data);

        return $field;
    }

    public function destroyField(
        Request $request,
        Farm $farm,
        FieldModel $field
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeField($farm, $field);

        $field->delete();

        return response()->json(null, 204);
    }

    private function authorizeField(
        Farm $farm,
        FieldModel $field
    ): void {
        if ($field->farm_id !== $farm->id) {
            throw new HttpException(
                404,
                'Field not found on this farm.'
            );
        }
    }

    // ============================================================
    // CROPS
    // ============================================================

    public function crops(
        Request $request,
        Farm $farm
    ) {
        $this->authorizeFarm($request, $farm);

        return $farm->crops()
            ->with('field')
            ->get();
    }

    public function storeCrop(
        Request $request,
        Farm $farm
    ) {
        $this->authorizeFarm($request, $farm);

        $data = $request->validate([
            'field_id' => [
                'nullable',
                'exists:fields,id',
            ],

            'name' => [
                'required',
                'string',
            ],

            'variety' => [
                'nullable',
                'string',
            ],

            'planting_date' => [
                'nullable',
                'date',
            ],

            'expected_harvest_date' => [
                'nullable',
                'date',
            ],

            'quantity_planted' => [
                'nullable',
                'numeric',
            ],

            'growth_stage' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'string',
            ],
        ]);

        if (!empty($data['field_id'])) {
            $field = FieldModel::find(
                $data['field_id']
            );

            $this->authorizeField(
                $farm,
                $field
            );
        }

        $crop = $farm->crops()->create($data);

        return response()->json(
            $crop,
            201
        );
    }

    public function updateCrop(
        Request $request,
        Farm $farm,
        Crop $crop
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);

        $data = $request->validate([
            'field_id' => [
                'nullable',
                'exists:fields,id',
            ],

            'name' => [
                'required',
                'string',
            ],

            'variety' => [
                'nullable',
                'string',
            ],

            'planting_date' => [
                'nullable',
                'date',
            ],

            'expected_harvest_date' => [
                'nullable',
                'date',
            ],

            'quantity_planted' => [
                'nullable',
                'numeric',
            ],

            'growth_stage' => [
                'nullable',
                'string',
            ],

            'image' => [
                'nullable',
                'string',
            ],
        ]);

        if (!empty($data['field_id'])) {
            $field = FieldModel::find(
                $data['field_id']
            );

            $this->authorizeField(
                $farm,
                $field
            );
        }

        $crop->update($data);

        return $crop;
    }

    public function destroyCrop(
        Request $request,
        Farm $farm,
        Crop $crop
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);

        $crop->delete();

        return response()->json(null, 204);
    }

    private function authorizeCrop(
        Farm $farm,
        Crop $crop
    ): void {
        if ($crop->farm_id !== $farm->id) {
            throw new HttpException(
                404,
                'Crop not found on this farm.'
            );
        }
    }

    // ============================================================
    // WATERING LOGS
    // ============================================================

    public function wateringLogs(Request $request)
    {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $logs = $farm->wateringLogs()
            ->with([
                'crop',
                'field',
            ])
            ->orderByDesc('watering_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    // ============================================================
    // CREATE WATERING LOG
    // ============================================================

    public function storeWateringLog(
        Request $request
    ) {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            throw new HttpException(
                404,
                'You do not have a farm yet.'
            );
        }

        $data = $request->validate([
            'crop_id' => [
                'required',
                'exists:crops,id',
            ],

            'field_id' => [
                'nullable',
                'exists:fields,id',
            ],

            'watering_date' => [
                'required',
                'date',
            ],

            'water_amount' => [
                'nullable',
                'numeric',
                'min:0',
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
            ->where(
                'id',
                $data['crop_id']
            )
            ->where(
                'farm_id',
                $farm->id
            )
            ->first();

        if (!$crop) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The selected crop does not belong to your farm.',
            ], 422);
        }

        // --------------------------------------------------------
        // Use crop field automatically when field is not provided
        // --------------------------------------------------------

        $fieldId = $data['field_id']
            ?? $crop->field_id;

        // --------------------------------------------------------
        // Make sure field belongs to this farm
        // --------------------------------------------------------

        if ($fieldId) {
            $field = FieldModel::find($fieldId);

            if (!$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field not found.',
                ], 422);
            }

            $this->authorizeField(
                $farm,
                $field
            );
        }

        // --------------------------------------------------------
        // Create watering log
        // --------------------------------------------------------

        $log = $crop->wateringLogs()->create([
            'farm_id' => $farm->id,
            'field_id' => $fieldId,
            'watering_date' =>
                $data['watering_date'],
            'water_amount' =>
                $data['water_amount'] ?? null,
            'notes' =>
                $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Watering log created successfully.',
            'data' => $log->load([
                'crop',
                'field',
            ]),
        ], 201);
    }

    // ============================================================
    // SHOW WATERING LOG
    // ============================================================

    public function showWateringLog(
        Request $request,
        $wateringLog
    ) {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            throw new HttpException(
                404,
                'You do not have a farm yet.'
            );
        }

        $log = $farm->wateringLogs()
            ->with([
                'crop',
                'field',
            ])
            ->findOrFail($wateringLog);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    // ============================================================
    // UPDATE WATERING LOG
    // ============================================================

    public function updateWateringLog(
        Request $request,
        $wateringLog
    ) {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            throw new HttpException(
                404,
                'You do not have a farm yet.'
            );
        }

        $log = $farm->wateringLogs()
            ->findOrFail($wateringLog);

        $data = $request->validate([
            'crop_id' => [
                'sometimes',
                'exists:crops,id',
            ],

            'field_id' => [
                'nullable',
                'exists:fields,id',
            ],

            'watering_date' => [
                'sometimes',
                'date',
            ],

            'water_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        // --------------------------------------------------------
        // Validate crop if it is being changed
        // --------------------------------------------------------

        if (isset($data['crop_id'])) {
            $crop = Crop::query()
                ->where(
                    'id',
                    $data['crop_id']
                )
                ->where(
                    'farm_id',
                    $farm->id
                )
                ->first();

            if (!$crop) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'The selected crop does not belong to your farm.',
                ], 422);
            }
        }

        // --------------------------------------------------------
        // Validate field
        // --------------------------------------------------------

        if (
            array_key_exists(
                'field_id',
                $data
            )
            && $data['field_id'] !== null
        ) {
            $field = FieldModel::find(
                $data['field_id']
            );

            if (!$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Field not found.',
                ], 422);
            }

            $this->authorizeField(
                $farm,
                $field
            );
        }

        $log->update($data);

        return response()->json([
            'success' => true,
            'message' =>
                'Watering log updated successfully.',
            'data' => $log->fresh()->load([
                'crop',
                'field',
            ]),
        ]);
    }

    // ============================================================
    // DELETE WATERING LOG
    // ============================================================

    public function destroyWateringLog(
        Request $request,
        $wateringLog
    ) {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            throw new HttpException(
                404,
                'You do not have a farm yet.'
            );
        }

        $log = $farm->wateringLogs()
            ->findOrFail($wateringLog);

        $log->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Watering log deleted successfully.',
        ]);
    }
}