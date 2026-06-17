<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeveloperModeController extends Controller
{
    /**
     * Get the active tokens for the user.
     */
    public function getTokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->latest()
            ->get(['id', 'name', 'last_used_at', 'created_at']);

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Generate a new developer token, revoking any old Developer Keys.
     */
    public function generateToken(Request $request): JsonResponse
    {
        // Create the new token
        $tokenResult = $request->user()->createToken('Developer Key');

        return response()->json([
            'success' => true,
            'token' => $tokenResult->plainTextToken,
            'token_id' => $tokenResult->accessToken->id,
            'created_at' => $tokenResult->accessToken->created_at,
        ]);
    }

    /**
     * Revoke a specific token by ID.
     */
    public function revokeToken(Request $request, $id): JsonResponse
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'API Key revoked successfully.'
        ]);
    }
}
