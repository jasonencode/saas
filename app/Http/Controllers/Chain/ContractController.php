<?php

namespace App\Http\Controllers\Chain;

use App\Enums\BlockChain\ContractDeployStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Chain\ContractResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\Contract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contracts = Contract::ofDeployed()
            ->with(['network'])
            ->when($request->filled('name'), function (Builder $builder, string $name) {
                $builder->where('name', 'like', "%$name%");
            })
            ->when($request->filled('type'), function (Builder $builder, string $type) {
                $builder->where('type', $type);
            })
            ->latest()
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ContractResource::collection($contracts));
    }

    public function show(Contract $contract): JsonResponse
    {
        if ($contract->deploy_status !== ContractDeployStatus::Deployed) {
            return ApiResponse::notFound();
        }

        $contract->load(['network', 'deployer']);

        return ApiResponse::success(ContractResource::make($contract));
    }
}
