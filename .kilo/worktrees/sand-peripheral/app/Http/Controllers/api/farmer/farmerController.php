<?php
 
namespace App\Http\Controllers\Api\Farmer;
 
use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FieldModel;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;
use Psy\TabCompletion\Matcher\FunctionsMatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;

class farmerController extends Controller
{
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
    private function authorizeFarm(Request $request, Farm $farm): void
    {
        if($farm->user_id !== $request->user()->id){
            throw new HttpException(403, 'This farm does not belong to you.');
        }
    }

    // Feilds

    public function fields(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);
        return $farm->fields;
    }

    public function storeField(Request $request, Farm $farm)
    {
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

    public function updateField(Request $request, Farm $farm, FieldModel $field)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeFiels($farm, $field);

        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'area' => ['nullable', 'numeric'],
            'soil_type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);
        $field->update($data);
        return $field;
    }

    public function destroyField(Request $request, Farm $farm, FieldModel $field)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeField($farm, $field);
        $field->delete();

        return response()->json(null, 204);
    }

    private function authorizeField(Farm $farm, FieldModel $field): void
    {
        if ($field->farm_id !== $farm->id)
        {
            throw new HttpException(404, 'Field not found on this farm.');
        }    
}

// Crops

    public function crops(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);
        return $farm->crops()->with('field')->get();
    }

    public function storeCrop(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        $date = $request->validate([
            'field_id' => ['nullable', 'exists:fields,id'],
            'name' => ['required', 'string'],
            'variety' => ['nullable', 'string'],
            'planting_date' => ['nullable','date'],
            'expected_harvest_date' => ['nullable','date'],
            'quantity_planted' => ['nullable', 'numeric'],
            'growth_stage' => ['nullable','string'],
            'image' => ['nullable', 'string'],
        ]);

        if (!empty($data['field_id'])){
            $field = FieldModel::find($data['field_id']);
            $this->authorizeField($farm, $field);
        }

        $crop = $farm->crops()->create($data);
        return response()->json($crop, 201);
    }
    public function updateCrop(Request $request, Farm $farm, Crop $crop)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);

        $data = $request->validate([
             'field_id' => ['nullable', 'exists:fields,id'],
            'name' => ['required', 'string'],
            'variety' => ['nullable', 'string'],
            'planting_date' => ['nullable','date'],
            'expected_harvest_date' => ['nullable','date'],
            'quantity_planted' => ['nullable', 'numeric'],
            'growth_stage' => ['nullable','string'],
            'image' => ['nullable', 'string'],
        ]);

        if(!empty($data['field_id'])){
            $field = FieldModel::find($data['field_id']);
            $this->authorizeField($farm, $field);
        }

        $crop->update($data);
        return $crop;
    }

    public function destroyCrop(Request $request, Farm $farm, Crop $crop)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);
        $crop->delete();

        return response()->json(null, 204);
    }

    private function authorizeCrop(Farm $farm, Crop $crop): void
    {
        if ($crop->farm_id !== $farm->id){
            throw new HttpException(404, 'Crop not found on this farm.');
        }
    }
    
    //Watering harvest logs

    public function storeWateringLog(Request $request, Farm $farm, Crop $crop)
    {
         $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);
 
        $data = $request->validate([
            'field_id' => ['nullable', 'exists:fields,id'],
            'date' => ['required', 'date'],
            'water_amount' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);
 
        if (! empty($data['field_id'])) {
            $field = FieldModel::find($data['field_id']);
            $this->authorizeField($farm, $field);
        }
 
        $log = $crop->wateringLogs()->create($data);
 
        return response()->json($log, 201);
    }

    public function storeHarvestLog(Request $request, Farm $farm, Crop $crop){
        $this->authorizeFarm($request, $farm);
        $this->authorizeCrop($farm, $crop);
 
        $data = $request->validate([
            'harvest_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric'],
            'quality' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
 
        $log = $crop->harvestLogs()->create($data);
 
        return response()->json($log, 201);
    }
}