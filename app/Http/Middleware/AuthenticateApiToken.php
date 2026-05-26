<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return $this->unauthenticatedResponse();
        }

        $hashedToken = hash('sha256', $plainToken);

        $apiToken = UserApiToken::query()
            ->with('user')
            ->where('token_hash', $hashedToken)
            ->whereNull('revoked_at')
            ->first();

        if (! $apiToken || ! $apiToken->user) {
            return $this->unauthenticatedResponse();
        }

        if ($apiToken->expires_at === null || $apiToken->expires_at->isPast()) {
            $apiToken->update(['revoked_at' => now()]);

            return $this->unauthenticatedResponse('Token telah kedaluwarsa. Silakan login kembali.');
        }

        $user = $apiToken->user;
        $request->attributes->set('api_token_hash', $hashedToken);
        $request->attributes->set('api_token_id', $apiToken->id);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function unauthenticatedResponse(string $message = 'Unauthenticated.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
