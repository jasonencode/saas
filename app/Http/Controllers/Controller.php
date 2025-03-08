<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

abstract class Controller
{
    public function success(mixed $data = null, int $code = SymfonyResponse::HTTP_OK, array $header = []): JsonResponse
    {
        return Response::json($data, $code, $header);
    }

    public function error(
        string $message = null,
        int $code = SymfonyResponse::HTTP_FORBIDDEN,
        array $header = []
    ): JsonResponse
    {
        return Response::json(['message' => $message], $code, $header);
    }

    protected function checkPermission(Model $model): void
    {
        if ($model->user && $model->user->isNot(Auth::user())) {
            throw new AuthorizationException();
        }
    }
}
