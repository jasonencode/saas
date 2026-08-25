<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\OrderRequest;
use App\Http\Resources\Mall\OrderCollection;
use App\Http\Resources\Mall\OrderLogResource;
use App\Http\Resources\Mall\OrderResource;
use App\Http\Resources\Mall\OrderShippingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Order;
use App\Models\Mall\Sku;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    /**
     * 获取订单列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 订单列表
     */
    public function index(Request $request): JsonResponse
    {
        $list = Order::ofUser(Auth::user())
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                $builder->where('status', $request->status);
            })
            ->when($request->filled('keyword'), function (Builder $builder) use ($request) {
                $keyword = addcslashes($request->keyword, '%_');
                $builder->where(function (Builder $query) use ($keyword) {
                    $query->search('no', $keyword)
                        ->orWhereHas('items', function (Builder $q) use ($keyword) {
                            $q->whereHas('product', function (Builder $p) use ($keyword) {
                                $p->search('name', $keyword);
                            });
                        });
                });
            })
            ->latest()
            ->with(['items.product', 'address'])
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(OrderCollection::make($list));
    }

    /**
     * 获取订单详情
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 订单详情
     */
    public function show(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $order->load(['items.product', 'items.sku', 'address']);

        return ApiResponse::success(OrderResource::make($order));
    }

    /**
     * 创建订单
     *
     * @param  OrderRequest  $request  创建订单请求
     *
     * @return JsonResponse 创建结果
     */
    public function create(OrderRequest $request): JsonResponse
    {
        // 创建原子锁，防止订单重复创建
        $lock = Cache::lock('mall_order_'.Auth::id(), 30);

        if ($lock->get()) {
            try {
                $items = Arr::map($request->safe()->offsetGet('items'), static function (array $item) {
                    $sku = Sku::find($item['product_sku_id']);

                    if (!$sku) {
                        throw new RuntimeException("商品规格不存在: {$item['product_sku_id']}");
                    }

                    return OrderItemDto::make($sku, $item['qty'], $item['remark'] ?? '');
                });

                service(OrderService::class)
                    ->createOrder(
                        tenant: TenantResolver::current(),
                        user: Auth::user(),
                        items: $items,
                        fulfillmentType: FulfillmentType::from($request->safe()->string('fulfillment_type')),
                        address: $request->safe()->integer('address_id'),
                        remark: $request->safe()->string('remark'),
                        pickupPointId: $request->safe()->integer('pickup_point_id')
                    );

                return ApiResponse::created();
            } catch (Throwable $e) {
                return ApiResponse::error($e->getMessage());
            } finally {
                $lock->release();
            }
        } else {
            return ApiResponse::error('请勿重复提交订单');
        }
    }

    /**
     * 取消订单
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 取消结果
     */
    public function cancel(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(OrderService::class)
                ->cancel($order, Auth::user());

            return ApiResponse::noContent('订单取消成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 删除订单
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 删除结果
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(OrderService::class)
                ->delete($order, Auth::user());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::noContent('订单删除成功');
    }

    /**
     * 获取订单物流信息
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 订单物流信息
     */
    public function shipping(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $shippings = $order->shippings()
            ->with(['express', 'items.product', 'items.sku'])
            ->get();

        return ApiResponse::success(OrderShippingResource::collection($shippings));
    }

    /**
     * 获取订单操作日志
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 订单操作日志
     */
    public function logs(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $logs = $order->logs()
            ->with('operator')
            ->latest()
            ->get();

        return ApiResponse::success(OrderLogResource::collection($logs));
    }

    /**
     * 获取常用订单状态数量统计
     *
     * @return JsonResponse 各状态订单数量
     */
    public function statusCount(): JsonResponse
    {
        $user = Auth::user();
        $pendingCount = Order::ofUser($user)
            ->where('status', OrderStatus::Pending)
            ->count();
        $waitShippingCount = Order::ofUser($user)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Preparing])
            ->count();
        $waitReceiveCount = Order::ofUser($user)
            ->whereIn('status', [OrderStatus::PartiallyShipped, OrderStatus::Delivered])
            ->count();

        return ApiResponse::success([
            'pending' => $pendingCount,
            'wait_shipping' => $waitShippingCount,
            'wait_receive' => $waitReceiveCount,
        ]);
    }

    /**
     * 确认收货
     *
     * @param  Order  $order  订单
     *
     * @return JsonResponse 确认收货结果
     */
    public function sign(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(OrderService::class)
                ->sign($order, Auth::user());

            return ApiResponse::noContent('订单确认收货成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
