<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\RefundRequest;
use App\Http\Requests\Mall\ShipReturnRequest;
use App\Http\Resources\Mall\RefundCollection;
use App\Http\Resources\Mall\RefundResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\RefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RefundController extends Controller
{
    /**
     * 申请退款
     *
     * @param  RefundRequest  $request  退款申请请求
     * @param  Order  $order  订单
     *
     * @return JsonResponse 退款单信息
     */
    public function store(RefundRequest $request, Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            $refund = service(RefundService::class)
                ->createRefund(
                    order: $order,
                    user: Auth::user(),
                    data: RefundData::make(
                        type: RefundType::from($request->safe()->offsetGet('type')),
                        reason: RefundReason::from($request->safe()->offsetGet('reason')),
                        reasonDetail: $request->safe()->offsetGet('reason_detail'),
                        items: collect($this->resolveItems($order, $request->safe()->offsetGet('items')))
                            ->map(fn (array $item): RefundItemData => RefundItemData::make(
                                orderItemId: (int) $item['order_item_id'],
                                qty: (int) $item['qty'],
                                price: $item['price'] ?? null,
                            ))
                            ->all(),
                    ),
                );

            $refund->load(['order', 'items.orderItem', 'express']);

            return ApiResponse::created(new RefundResource($refund));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 获取退款列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 退款列表
     */
    public function index(Request $request): JsonResponse
    {
        $list = Refund::ofUser(Auth::user())
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                $builder->where('status', $request->string('status'));
            })
            ->latest()
            ->with(['order', 'items.orderItem', 'express'])
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(RefundCollection::make($list));
    }

    /**
     * 获取退款详情
     *
     * @param  Refund  $refund  退款单
     *
     * @return JsonResponse 退款详情
     */
    public function show(Refund $refund): JsonResponse
    {
        if ($refund->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $refund->load(['order', 'items.orderItem', 'express.express', 'logs']);

        return ApiResponse::success(RefundResource::make($refund));
    }

    /**
     * 取消退款
     *
     * @param  Refund  $refund  退款单
     *
     * @return JsonResponse 取消结果
     */
    public function cancel(Refund $refund): JsonResponse
    {
        if ($refund->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(RefundService::class)
                ->cancelRefund($refund, Auth::user());

            return ApiResponse::noContent('退款已取消');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 提交退货物流
     *
     * @param  ShipReturnRequest  $request  退货物流请求
     * @param  Refund  $refund  退款单
     *
     * @return JsonResponse 提交结果
     */
    public function ship(ShipReturnRequest $request, Refund $refund): JsonResponse
    {
        if ($refund->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(RefundService::class)
                ->shipReturn(
                    refund: $refund,
                    user: Auth::user(),
                    expressData: [
                        'express_id' => $request->safe()->integer('express_id'),
                        'express_no' => (string) $request->safe()->string('express_no'),
                    ]
                );

            return ApiResponse::noContent('退货物流提交成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 解析退款商品，价格以订单明细为准
     *
     * @param  Order  $order  订单
     * @param  array  $items  请求中的退款商品（order_item_id, qty）
     *
     * @return array<int, array{order_item_id: int, qty: int, price: string}> 规范化后的退款商品
     */
    private function resolveItems(Order $order, array $items): array
    {
        $orderItems = OrderItem::whereIn('id', Arr::pluck($items, 'order_item_id'))
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('id');

        return Arr::map($items, static function (array $item) use ($orderItems): array {
            $orderItem = $orderItems->get($item['order_item_id']);

            return [
                'order_item_id' => (int) $item['order_item_id'],
                'qty' => (int) $item['qty'],
                'price' => $orderItem?->price ?? '0.00',
            ];
        });
    }
}
