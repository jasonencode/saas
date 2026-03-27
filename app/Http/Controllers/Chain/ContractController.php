<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function show(Contract $contract): JsonResponse
    {
        return ApiResponse::success($contract);
    }
}
