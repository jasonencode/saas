<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\Campaign\SeckillActivityStatus;
use App\Enums\Campaign\SeckillSessionStatus;
use App\Enums\Mall\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\SeckillOrderRequest;
use App\Http\Resources\Campaign\SeckillActivityResource;
use App\Http\Resources\Campaign\SeckillSessionResource;
use App\Http\Resources\Mall\OrderCreatedResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\SeckillActivity;
use App\Models\Campaign\SeckillSession;
use App\Services\Mall\Seckill\SeckillOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 秒杀前台接口
 */
class SeckillController extends Controller
{
    /**
     * 秒杀活动列表（进行中 + 预告场次）
     */
    public function index(): JsonResponse
    {
        $activities = SeckillActivity::query()
            ->where('status', SeckillActivityStatus::Published->value)
            ->with([
                'sessions' => fn ($query) => $query
                    ->whereIn('status', [
                        SeckillSessionStatus::Scheduled->value,
                        SeckillSessionStatus::Running->value,
                    ])
                    ->with(['product', 'sku'])
                    ->orderBy('start_at'),
            ])
            ->orderBy('sort')
            ->get();

        return ApiResponse::success(SeckillActivityResource::collection($activities));
    }

    /**
     * 场次详情
     */
    public function show(Request $request, SeckillSession $session): JsonResponse
    {
        $session->loadMissing(['activity', 'product', 'sku']);

        if (!$session->activity?->isPublished()) {
            return ApiResponse::notFound('秒杀场次不存在');
        }

        return ApiResponse::success(new SeckillSessionResource($session));
    }

    /**
     * 秒杀下单
     */
    public function order(SeckillOrderRequest $request, SeckillSession $session): JsonResponse
    {
        $order = service(SeckillOrderService::class)->create(
            user: $request->user(),
            session: $session,
            qty: $request->integer('qty'),
            fulfillmentType: FulfillmentType::from($request->string('fulfillment_type')),
            address: $request->filled('address_id') ? $request->integer('address_id') : null,
            pickupPointId: $request->integer('pickup_point_id') ?: null,
            remark: $request->input('remark'),
        );

        return ApiResponse::created(OrderCreatedResource::collection(collect([$order])));
    }
}
