<?php

namespace App\Http\Controllers\Api\Auth;


use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required_without:phone', 'nullable', 'email', 'unique:users,email'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            
        ]);

        // Create a new verification code in the database for this user. Check if we are verifying their email or phone. Generate a random 6-digit code, and set it to expire in 15 minutes.
        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => $request->email ? 'email' : 'phone',
            'code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinute(15),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'code' => ['required', 'string'],
        ]);

        //find the most recent verification code for this user that matches the code they typed in, but ONLY if the code is still active and hasn't expired yet.
        $verification = UserVerification::where('user_id', $request->user_id)
            ->where('code', $request->code)
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        $user = User::findOrFail($request->user_id);

        if ($verification->type === 'email') {
            $user->email_verified_at = now();
        } else {
            $user->phone_verified_at = now();
        }
        $user->save();

        return response()->json(['message' => 'Verified', 'user' => $user]);
    }
    public function login(Request $request)

    /**
     * Authenticate user via email or phone and return a Sanctum access token[cite: 1].
     */
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (! $user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
