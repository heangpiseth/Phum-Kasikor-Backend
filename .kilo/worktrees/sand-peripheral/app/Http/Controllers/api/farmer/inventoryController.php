<?php

namespace App\Http\Controllers\Api\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InventoryController extends Controller
{
    // ---- Categories (shared reference data across all farmers) ----

    public function categories(Farm $farm)
    {
        return InventoryCategory::all();
    }

    public function storeCategory(Request $request, Farm $farm)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:inventory_categories,name'],
            'icon' => ['nullable', 'string', 'max:500'],
        ]);

        $category = InventoryCategory::create($data);

        return response()->json($category, 201);
    }

    // ---- Inventory, scoped to one of the farmer's farms ----

    public function index(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        return $farm->inventory()->with('category')->get();
    }

    public function store(Request $request, Farm $farm)
    {
        $this->authorizeFarm($request, $farm);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:30'],
            'minimum_stock' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:in_stock,low_stock,out_of_stock'],
        ]);

        $item = $farm->inventory()->create($data);

        return response()->json($item, 201);
    }

    public function update(Request $request, Farm $farm, Inventory $item)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeItem($farm, $item);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:30'],
            'minimum_stock' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:in_stock,low_stock,out_of_stock'],
        ]);

        $item->update($data);

        return $item;
    }

    public function destroy(Request $request, Farm $farm, Inventory $item)
    {
        $this->authorizeFarm($request, $farm);
        $this->authorizeItem($farm, $item);
        $item->delete();

        return response()->json(null, 204);
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        if ($farm->user_id !== $request->user()->id) {
            throw new HttpException(403, 'This farm does not belong to you.');
        }
    }

    private function authorizeItem(Farm $farm, Inventory $item): void
    {
        if ($item->farm_id !== $farm->id) {
            throw new HttpException(404, 'Inventory item not found on this farm.');
        }
    }
}