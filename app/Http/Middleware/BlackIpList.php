<?php

namespace App\Http\Middleware;

use App\Services\BlackListService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Symfony\Component\HttpFoundation\Response;

class BlackIpList
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (resolve(BlackListService::class)->inBlackList($request->ip())) {
            throw new ForbiddenException(403, 'Not allowed');
        }

        return $response;
    }
}
