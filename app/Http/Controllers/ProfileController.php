<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'data' => [
                'name' => $user->name,
                'nim' => $user->nim,
                'email' => $user->email,
                'image_url' => $user->image_url,
            ]
        ]);
    }
}
