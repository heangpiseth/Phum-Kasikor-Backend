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
    // FARM
    // ============================================================

    public function index(Request $request)
    {
        return response()->json(
            $request->user()
                ->farms()
                ->get()
        );
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

        $farm = $request->user()
            ->farms()
            ->create($data);

        return response()->json($farm, 201);
    }

    public function show(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        return response()->json($farm);
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

        return response()->json(
            $farm->fresh()
        );
    }

    public function destroy(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        $farm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Farm deleted successfully.',
        ]);
    }

    private function authorizeFarm(
        Request $request,
        Farm $farm
    ): void {
        if ((string) $farm->user_id !== (string) $request->user()->id) {
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

        return response()->json(
            $farm->fields()->get()
        );
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

        return response()->json(
            $field,
            201
        );
    }

    public function showField(
        Request $request,
        Farm $farm,
        FieldModel $field
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeField($farm, $field);

        return response()->json($field);
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

        return response()->json(
            $field->fresh()
        );
    }

    public function destroyField(
        Request $request,
        Farm $farm,
        FieldModel $field
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeField($farm, $field);

        $field->delete();

        return response()->json([
            'success' => true,
            'message' => 'Field deleted successfully.',
        ]);
    }

    private function authorizeField(
        Farm $farm,
        FieldModel $field
    ): void {
        if ((string) $field->farm_id !== (string) $farm->id) {
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

        return response()->json(
            $farm->crops()
                ->with('field')
                ->get()
        );
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

            if (!$field) {
                throw new HttpException(
                    422,
                    'Field not found.'
                );
            }

            $this->authorizeField(
                $farm,
                $field
            );
        }

        $crop = $farm->crops()->create($data);

        return response()->json(
            $crop->load('field'),
            201
        );
    }

    public function showCrop(
        Request $request,
        Farm $farm,
        Crop $crop
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);

        return response()->json(
            $crop->load('field')
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

            if (!$field) {
                throw new HttpException(
                    422,
                    'Field not found.'
                );
            }

            $this->authorizeField(
                $farm,
                $field
            );
        }

        $crop->update($data);

        return response()->json(
            $crop->fresh()->load('field')
        );
    }

    public function destroyCrop(
        Request $request,
        Farm $farm,
        Crop $crop
    ) {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);

        $crop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Crop deleted successfully.',
        ]);
    }

    private function authorizeCrop(
        Farm $farm,
        Crop $crop
    ): void {
        if ((string) $crop->farm_id !== (string) $farm->id) {
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

        $fieldId = $data['field_id']
            ?? $crop->field_id;

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
            'data' =>
                $log->load([
                    'crop',
                    'field',
                ]),
        ], 201);
    }

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
            'data' =>
                $log->fresh()->load([
                    'crop',
                    'field',
                ]),
        ]);
    }

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


    // ============================================================
    // EARNINGS
    // ============================================================

    public function earnings(Request $request)
    {
        $farm = $request->user()
            ->farms()
            ->first();

        if (!$farm) {
            return response()->json([
                'success' => true,
                'earnings' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'earnings' => 0,
        ]);
    }
}