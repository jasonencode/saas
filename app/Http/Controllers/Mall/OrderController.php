<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderScope;
use App\Enums\Mall\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\OrderIndexRequest;
use App\Http\Requests\Mall\OrderPreviewRequest;
use App\Http\Requests\Mall\OrderRequest;
use App\Http\Resources\Mall\OrderCollection;
use App\Http\Resources\Mall\OrderCreatedResource;
use App\Http\Resources\Mall\OrderLogResource;
use App\Http\Resources\Mall\OrderPreviewResource;
use App\Http\Resources\Mall\OrderResource;
use App\Http\Resources\Mall\OrderShippingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Delivery;
use App\Models\Mall\Order;
use App\Models\Mall\Sku;
use App\Models\User\Address;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DeliveryService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderableResolver;
use App\Services\Mall\OrderService;
use App\Services\Mall\ProductDiscountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    /**
     * 获取订单列表
     */
    public function index(OrderIndexRequest $request): JsonResponse
    {
        $list = Order::ofUser(Auth::user())
            ->when($request->validated('scope'), function (Builder $builder) use ($request) {
                OrderScope::from($request->validated('scope'))->apply($builder);
            })
            ->when($request->validated('keyword'), function (Builder $builder) use ($request) {
                $keyword = addcslashes($request->validated('keyword'), '%_');
                $builder->where(function (Builder $query) use ($keyword) {
                    $query->search('no', $keyword);
                });
            })
            ->latest()
            ->with(['items.orderable', 'address', 'refunds', 'tenant.storeConfigure', 'pickupPoint'])
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

        $order->load(['items.orderable', 'address', 'tenant.storeConfigure', 'pickupPoint', 'refunds']);

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
        // 实体商品按身份折扣实时取价，其余主体取原价
        $price = $orderable instanceof Sku
            ? service(ProductDiscountService::class)->priceFor(Auth::user(), $orderable)
            : $orderable->getOrderablePrice();
        $totalAmount = bcmul($price, (string) $qty, 2);

        $addresses = Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        $address = $request->filled('address_id')
            ? Address::find($request->safe()->integer('address_id'))
            : null;
        $freight = '0.00';

        if ($fulfillmentType === FulfillmentType::Mail && $address && $address->user->is(Auth::user()) && $orderable instanceof Sku) {
            $deliveryService = service(DeliveryService::class);

            $deliveryId = $orderable->product?->delivery_id ?? 'default';
            $delivery = $deliveryId === 'default'
                ? $deliveryService->getDefaultForTenant($request->attributes->get('tenant')?->getKey())
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

        // 优惠券抵扣：券是租户维度，仅当券与其所属租户与商品一致时生效
        $couponUserId = $request->safe()->integer('coupon_user_id');
        $couponDiscount = '0.00';

        if ($couponUserId) {
            $couponUser = CouponUser::query()->find($couponUserId);

            if (!$couponUser) {
                return ApiResponse::error('优惠券不存在');
            }

            $couponItems = (int) $couponUser->coupon?->tenant_id === $orderable->getTenantId()
                ? collect([OrderItemDto::forPreview($orderable, $qty, $orderable instanceof Sku ? $price : null)])
                : collect();

            try {
                $couponDiscount = service(CouponService::class)
                    ->previewDiscount($couponUser, Auth::user(), $couponItems)['discount'];
            } catch (InvalidArgumentException $e) {
                return ApiResponse::error($e->getMessage());
            }
        }

        return ApiResponse::success(OrderPreviewResource::make((object) [
            'item' => (object) [
                'orderable' => $orderable,
                'qty' => $qty,
                'price' => $price,
                'sub_total' => $totalAmount,
            ],
            'addresses' => $addresses,
            'address' => $address,
            'goods_amount' => $totalAmount,
            'coupon_discount' => $couponDiscount,
            'freight' => $freight,
            'payable_amount' => bcsub(bcadd($totalAmount, $freight, 2), $couponDiscount, 2),
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

                // 实体商品按身份折扣实时取价，其余主体取原价
                $price = $orderable instanceof Sku
                    ? service(ProductDiscountService::class)->priceFor(Auth::user(), $orderable)
                    : null;

                $items = [OrderItemDto::make($orderable, $request->safe()->integer('qty'), $request->safe()->string('remark'), $price)];

                $orders = service(OrderService::class)
                    ->createOrders(
                        user: Auth::user(),
                        items: $items,
                        fulfillmentType: FulfillmentType::from($request->safe()->string('fulfillment_type')),
                        address: $request->filled('address_id') ? $request->safe()->integer('address_id') : null,
                        pickupPointId: $request->safe()->integer('pickup_point_id'),
                        couponUserId: $request->safe()->integer('coupon_user_id') ?: null
                    );

                return ApiResponse::created(OrderCreatedResource::collection($orders));
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

        $tabs = [];
        foreach (OrderScope::cases() as $tab) {
            $query = Order::ofUser($user);
            $tab->apply($query);
            $tabs[$tab->value] = $query->count();
        }

        $tabs['refunding'] = Order::ofUser($user)
            ->whereHas('refunds', fn ($q) => $q->whereIn('status', [
                RefundStatus::Pending,
                RefundStatus::WaitingReturn,
                RefundStatus::Shipping,
                RefundStatus::Received,
                RefundStatus::Processing,
            ]))
            ->count();
        $tabs['available_coupons'] = $user->coupons()
            ->where('is_used', false)
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->count();

        return ApiResponse::success($tabs);
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
