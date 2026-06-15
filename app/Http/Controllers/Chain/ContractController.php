<?php

namespace App\Http\Controllers\Chain;

use App\Enums\BlockChain\ContractDeployStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\Contract;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success();
    }

    public function show(Contract $contract): JsonResponse
    {
        if ($contract->deploy_status !== ContractDeployStatus::Deployed) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success($contract);
    }
}
