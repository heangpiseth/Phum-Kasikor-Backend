<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FirebaseAuthController extends Controller
{
    private FirebaseAuth $firebaseAuth;

    public function __construct()
    {
        $this->firebaseAuth = (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'))
            ->createAuth();
    }

    /**
     * Verifies the Firebase token from Google/Facebook sign-in.
     * Finds the matching account, links to an existing email-based
     * account if one exists, or creates a brand new (roleless) user.
     * Always returns a token - the frontend checks `is_new` to decide
     * whether to route into choose-role/profile-setup/location-setup
     * or straight into the app.
     */
    public function verifyToken(Request $request)
{
    $request->validate([
        'id_token' => ['required', 'string'],
    ]);

    $claims = $this->decodeIdToken($request->id_token)->claims();

    $firebaseUid = $claims->get('sub');
    $email = $claims->get('email');
    $name = $claims->get('name');

    /*
    |--------------------------------------------------------------------------
    | 1. Find existing Firebase user
    |--------------------------------------------------------------------------
    */

    $user = User::where('firebase_uid', $firebaseUid)->first();

    $isNew = false;

    /*
    |--------------------------------------------------------------------------
    | 2. Try to link an existing email account
    |--------------------------------------------------------------------------
    */

    if (! $user && $email) {
        $user = User::where('email', $email)
            ->whereNull('firebase_uid')
            ->first();

        if ($user) {
            $user->update([
                'firebase_uid' => $firebaseUid,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Create new Laravel user
    |--------------------------------------------------------------------------
    */

    if (! $user) {
        $user = User::create([
            'name' => $name ?? 'New User',
            'email' => $email,
            'firebase_uid' => $firebaseUid,
            'email_verified_at' => $email ? now() : null,
        ]);

        $isNew = true;
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Create Laravel Sanctum token
    |--------------------------------------------------------------------------
    */

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'user' => $user->fresh(),
        'token' => $token,
        'is_new' => $isNew,
    ]);
}

    private function decodeIdToken(string $idToken)
    {
        try {
            return $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            throw new HttpException(401, 'Invalid or expired Firebase token.');
        }
    }
}