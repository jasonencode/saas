<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\Certificate;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function create(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function show(Certificate $certificate): JsonResponse
    {
        return ApiResponse::success($certificate);
    }
}
