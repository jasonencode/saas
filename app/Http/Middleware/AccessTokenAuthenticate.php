<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\System\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AccessTokenAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $encoded = $request->query('access_token');

        if (!$encoded) {
            return ApiResponse::unauthorized('Access token is required');
        }

        $token = base64_decode($encoded, true);

        if (!$token) {
            return ApiResponse::unauthorized('Access token is invalid');
        }

        $pat = PersonalAccessToken::findToken($token);

        if (!$pat || $pat->tokenable_type !== Tenant::class) {
            return ApiResponse::unauthorized('Access token is unauthorized');
        }

        $pat->last_used_at = now();
        $pat->save();

        Auth::setUser($pat->tokenable);

        return $next($request);
    }
}
