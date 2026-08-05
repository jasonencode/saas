<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Chain\ChainAddressResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\ChainAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * 获取链地址列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 链地址列表
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = ChainAddress::with(['network'])
            ->when($request->filled('network_id'), function ($builder, int $networkId) {
                $builder->where('network_id', $networkId);
            })
            ->latest()
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ChainAddressResource::collection($addresses));
    }
}
