<?php

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Services\TokenService;
use Core\Http\Message\Request;
use Core\Http\Message\Response;

class TokenController extends Controller
{
    public function __construct(private TokenService $tokenService)
    {
    }

    /**
     * Refresh the current token.
     */
    public function refresh(Request $request): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return Response::json(['error' => 'Token required'], 401);
        }

        $userId = (int) $request->getAttribute('auth_user_id');
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        return Response::json($this->tokenService->refresh($token, $userId));
    }

    /**
     * Revoke the current token.
     */
    public function logout(Request $request): Response
    {
        $token = $request->bearerToken();
        if ($token) {
            $this->tokenService->revoke($token);
        }

        return Response::json(['message' => 'Token revoked']);
    }

    /**
     * Revoke all tokens for the current user.
     */
    public function logoutAll(Request $request): Response
    {
        $userId = (int) $request->getAttribute('auth_user_id');
        if ($userId) {
            $this->tokenService->revokeAll($userId);
        }

        return Response::json(['message' => 'All tokens revoked']);
    }
}
