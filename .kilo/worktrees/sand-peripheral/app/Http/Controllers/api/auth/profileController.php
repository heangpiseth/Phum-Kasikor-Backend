<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return $request->user();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'profile_image' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->update($data);

        return $request->user()->fresh();
    }

    public function chooseRole(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $request->user()->update($data);

        return $request->user();
    }

    public function setupProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'profile_image' => ['nullable', 'string', 'max:500'],
            'farm_name' => ['nullable', 'string', 'max:255'], // farmer only
        ]);

        $user->update(collect($data)->except('farm_name')->toArray());

        // Farmers get a starter Farm record created from this one field;
        // full farm details (location, images, etc.) get filled in later
        // via the existing farmer/farms endpoints.
        if ($user->isFarmer() && ! empty($data['farm_name'])) {
            $farm = $user->farms()->first();

            if ($farm) {
                $farm->update(['farm_name' => $data['farm_name']]);
            } else {
                $user->farms()->create(['farm_name' => $data['farm_name']]);
            }
        }

        return $user->fresh();
    }

    public function setupLocation(Request $request)
    {
        $data = $request->validate([
            'province' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $location = collect([$data['province'], $data['district'] ?? null, $data['commune'] ?? null])
            ->filter()
            ->implode(', ');

        $request->user()->update([
            'location' => $location,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return $request->user();
    }
}