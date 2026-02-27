<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }

    public function show(Contract $contract): JsonResponse
    {
        return $this->success($contract);
    }
}