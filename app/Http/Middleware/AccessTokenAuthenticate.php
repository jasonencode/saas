<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
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
            return response()->json(['message' => 'Access token is required'], 401);
        }

        $token = base64_decode($encoded, true);

        if (!$token) {
            return response()->json(['message' => 'Access token is invalid'], 401);
        }

        $pat = PersonalAccessToken::findToken($token);

        if (!$pat || $pat->tokenable_type !== Tenant::class) {
            return response()->json(['message' => 'Access token is unauthorized'], 401);
        }

        $pat->last_used_at = now();
        $pat->save();

        Auth::setUser($pat->tokenable);

        return $next($request);
    }

}