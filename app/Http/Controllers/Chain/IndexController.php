<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Chain\NetworkResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\Network;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    /**
     * 获取区块链网络列表
     *
     * @return JsonResponse 区块链网络列表
     */
    public function networks(): JsonResponse
    {
        $list = Network::ofEnabled()->get();

        return ApiResponse::success(NetworkResource::collection($list));
    }
}
