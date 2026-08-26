<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\OrderPreviewRequest;
use App\Http\Requests\Mall\OrderRequest;
use App\Http\Resources\Mall\OrderCollection;
use App\Http\Resources\Mall\OrderLogResource;
use App\Http\Resources\Mall\OrderPreviewResource;
use App\Http\Resources\Mall\OrderResource;
use App\Http\Resources\Mall\OrderShippingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Delivery;
use App\Models\Mall\Order;
use App\Models\Mall\Sku;
use App\Models\User\Address;
use App\Services\Mall\DeliveryService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderableResolver;
use App\Services\Mall\OrderService;
use App\Support\TenantResolver\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    /**
     * 获取订单列表
     */
    public function index(Request $request): JsonResponse
    {
        $list = Order::ofUser(Auth::user())
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                $builder->whereIn('status', explode(',', $request->status));
            })
            ->when($request->filled('keyword'), function (Builder $builder) use ($request) {
                $keyword = addcslashes($request->keyword, '%_');
                $builder->where(function (Builder $query) use ($keyword) {
                    $query->search('no', $keyword);
                });
            })
            ->latest()
            ->with(['items.orderable', 'address'])
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(OrderCollection::make($list));
    }

    /**
     * 获取订单详情
     */
    public function show(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $order->load(['items.orderable', 'address']);

        return ApiResponse::success(OrderResource::make($order));
    }

    /**
     * 立即购买商品，结算预览
     */
    public function preview(OrderPreviewRequest $request): JsonResponse
    {
        $fulfillmentType = FulfillmentType::from($request->safe()->string('fulfillment_type'));

        $orderable = OrderableResolver::resolve(
            $request->safe()->string('orderable_type'),
            $request->safe()->integer('orderable_id')
        );

        if (!$orderable) {
            return ApiResponse::error('商品不存在');
        }

        if (!$orderable->supportsFulfillmentType($fulfillmentType)) {
            return ApiResponse::error(sprintf('商品[%s]不支持[%s]履约方式', $orderable->getOrderableName(), $fulfillmentType->getLabel()));
        }

        $qty = $request->safe()->integer('qty');
        $price = $orderable->getOrderablePrice();
        $totalAmount = bcmul($price, (string) $qty, 2);

        $addresses = Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        $address = $request->filled('address_id')
            ? Address::find($request->safe()->integer('address_id'))
            : null;
        $freight = '0.00';

        if ($fulfillmentType === FulfillmentType::Mail && $address && $address->user->is(Auth::user()) && $orderable instanceof Sku) {
            $deliveryService = app(DeliveryService::class);

            $deliveryId = $orderable->product?->delivery_id ?? 'default';
            $delivery = $deliveryId === 'default'
                ? $deliveryService->getDefaultForTenant(TenantResolver::current()?->getKey())
                : Delivery::find($deliveryId);

            if ($delivery) {
                $freight = $deliveryService->calculateOrderFreight(
                    delivery: $delivery,
                    items: collect([(object) ['orderable' => $orderable, 'qty' => $qty]]),
                    provinceId: $address->province_id,
                    cityId: $address->city_id,
                    districtId: $address->district_id,
                );
            }
        }

        return ApiResponse::success(new OrderPreviewResource((object) [
            'item' => (object) [
                'orderable' => $orderable,
                'qty' => $qty,
                'price' => $price,
                'sub_total' => $totalAmount,
            ],
            'addresses' => $addresses,
            'address' => $address,
            'total_amount' => $totalAmount,
            'freight' => $freight,
            'payable_amount' => bcadd($totalAmount, $freight, 2),
        ]));
    }

    /**
     * 创建订单
     */
    public function create(OrderRequest $request): JsonResponse
    {
        $lock = Cache::lock('mall_order_'.Auth::id(), 30);

        if ($lock->get()) {
            try {
                $orderable = OrderableResolver::resolve(
                    $request->safe()->string('orderable_type'),
                    $request->safe()->integer('orderable_id')
                );

                if (!$orderable) {
                    throw new RuntimeException('商品不存在');
                }

                $items = [OrderItemDto::make($orderable, $request->safe()->integer('qty'), $request->safe()->string('remark'))];

                service(OrderService::class)
                    ->createOrders(
                        user: Auth::user(),
                        items: $items,
                        fulfillmentType: FulfillmentType::from($request->safe()->string('fulfillment_type')),
                        address: $request->safe()->integer('address_id'),
                        pickupPointId: $request->safe()->integer('pickup_point_id')
                    );

                return ApiResponse::created();
            } catch (Throwable $e) {
                return ApiResponse::error($e->getMessage());
            } finally {
                $lock->release();
            }
        } else {
            return ApiResponse::error('请勿重复提交订单', Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

    /**
     * 取消订单
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
     */
    public function shipping(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $shippings = $order->shippings()
            ->with(['express', 'items.orderable'])
            ->get();

        return ApiResponse::success(OrderShippingResource::collection($shippings));
    }

    /**
     * 获取订单操作日志
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
