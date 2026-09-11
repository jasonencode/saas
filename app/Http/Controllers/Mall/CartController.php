<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\CheckoutPreviewRequest;
use App\Http\Requests\Mall\OrderFromCartRequest;
use App\Http\Requests\Mall\StoreCartItemRequest;
use App\Http\Requests\Mall\UpdateCartItemRequest;
use App\Http\Resources\Mall\CartResource;
use App\Http\Resources\Mall\CheckoutResource;
use App\Http\Resources\Mall\OrderCreatedResource;
use App\Http\Responses\ApiResponse;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\CartItem;
use App\Models\Mall\Delivery;
use App\Models\Mall\Sku;
use App\Models\User\Address;
use App\Services\Campaign\CouponService;
use App\Services\Mall\CartService;
use App\Services\Mall\DeliveryService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use App\Services\Mall\ProductDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * 获取购物车列表
     *
     * @return JsonResponse 购物车详情
     */
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());

        $cart->load(['items.product.storeConfigure', 'items.sku']);

        return ApiResponse::success(CartResource::make($cart));
    }

    /**
     * 添加商品到购物车
     *
     * @param  StoreCartItemRequest  $request  添加购物车请求
     *
     * @return JsonResponse 购物车详情
     */
    public function add(StoreCartItemRequest $request): JsonResponse
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user());
            $sku = Sku::findOrFail($request->validated('sku_id'));
            $qty = (int) $request->validated('qty');

            $this->cartService->addItem($cart, $sku, $qty);

            $cart->load(['items.product.storeConfigure', 'items.sku']);

            return ApiResponse::success(CartResource::make($cart), '添加成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 结算预览
     *
     * @param  CheckoutPreviewRequest  $request  结算预览请求
     *
     * @return JsonResponse 结算预览信息
     */
    public function preview(CheckoutPreviewRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());
        $itemIds = $request->validated('item_ids');

        $cartItems = $cart->items()
            ->whereIn('id', $itemIds)
            ->with(['product', 'sku'])
            ->get();

        if ($cartItems->isEmpty()) {
            return ApiResponse::error('未找到有效的购物车商品');
        }

        // 下单所选履约方式
        $fulfillmentType = FulfillmentType::from($request->safe()->string('fulfillment_type'));

        // 校验所选履约方式被所有商品支持，任一不支持则拒绝
        $unsupported = $cartItems->first(
            fn ($item) => !in_array($fulfillmentType->value, $item->product?->fulfillment_type ?? [], true)
        );

        if ($unsupported) {
            return ApiResponse::error(sprintf('商品[%s]不支持[%s]履约方式', $unsupported->product?->name, $fulfillmentType->getLabel()));
        }

        // 计算商品总金额：实时按身份折扣取价，与下单口径一致（不引用 price_at_add 快照）
        $discountService = service(ProductDiscountService::class);
        $percentMap = $discountService->percentForProducts(Auth::user(), $cartItems->map(fn ($item) => $item->product)->unique('id')->values());

        $totalAmount = $cartItems->reduce(function ($carry, $item) use ($discountService, $percentMap) {
            $price = isset($percentMap[$item->product_id])
                ? $discountService->applyPercent($item->sku->getOrderablePrice(), $percentMap[$item->product_id])
                : $item->sku->getOrderablePrice();

            return bcadd($carry, bcmul($price, (string) $item->qty, 2), 2);
        }, '0.00');

        // 获取用户地址列表
        $addresses = Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        // 计算运费：仅快递邮寄（mail）履约方式按运费模板计费，门店自提/虚拟商品免运费
        $addressId = $request->safe()->integer('address_id');
        $address = $addressId ? Address::find($addressId) : null;
        $freight = '0.00';

        if ($fulfillmentType === FulfillmentType::Mail && $address && $address->user->is(Auth::user())) {
            $deliveryService = service(DeliveryService::class);

            // 跨店购物车：按「租户:运费模板」分组，默认模板按商品所属租户取，与下单拆单口径一致
            $groupedByDelivery = $cartItems->groupBy(function ($item) {
                return $item->product->tenant_id.':'.($item->product->delivery_id ?? 'default');
            });

            foreach ($groupedByDelivery as $key => $groupItems) {
                [$tenantId, $deliveryId] = explode(':', $key, 2);

                $delivery = $deliveryId === 'default'
                    ? $deliveryService->getDefaultForTenant((int) $tenantId)
                    : Delivery::find($deliveryId);

                if ($delivery) {
                    $freight = bcadd($freight, $deliveryService->calculateOrderFreight(
                        delivery: $delivery,
                        items: $groupItems,
                        provinceId: $address->province_id,
                        cityId: $address->city_id,
                        districtId: $address->district_id,
                    ), 2);
                }
            }
        }

        // 优惠券抵扣：券是租户维度，仅抵扣其所属租户的商品小计（与下单按租户拆单口径一致）
        $couponUserId = $request->safe()->integer('coupon_user_id');
        $couponDiscount = '0.00';

        if ($couponUserId) {
            $couponUser = CouponUser::query()->find($couponUserId);

            if (!$couponUser) {
                return ApiResponse::error('优惠券不存在');
            }

            $couponItems = $cartItems
                ->filter(fn ($item) => (int) $item->product?->tenant_id === (int) $couponUser->coupon?->tenant_id)
                ->map(fn ($item) => OrderItemDto::forPreview(
                    $item->sku,
                    (int) $item->qty,
                    isset($percentMap[$item->product_id])
                        ? $discountService->applyPercent($item->sku->getOrderablePrice(), $percentMap[$item->product_id])
                        : null
                ))
                ->values();

            try {
                $couponDiscount = service(CouponService::class)
                    ->previewDiscount($couponUser, Auth::user(), $couponItems)['discount'];
            } catch (InvalidArgumentException $e) {
                return ApiResponse::error($e->getMessage());
            }
        }

        return ApiResponse::success(CheckoutResource::make(collect([
            'items' => $cartItems,
            'percent_map' => $percentMap,
            'addresses' => $addresses,
            'address' => $address,
            'goods_amount' => $totalAmount,
            'coupon_discount' => $couponDiscount,
            'freight' => $freight,
            'payable_amount' => bcsub(bcadd($totalAmount, $freight, 2), $couponDiscount, 2),
        ])));
    }

    /**
     * 从购物车创建订单
     *
     * @param  OrderFromCartRequest  $request  从购物车创建订单请求
     *
     * @return JsonResponse 创建的订单列表
     */
    public function createFromCart(OrderFromCartRequest $request): JsonResponse
    {
        $lock = Cache::lock('mall_order_'.Auth::id(), 30);

        if (!$lock->get()) {
            return ApiResponse::error('请勿重复提交订单');
        }

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user());
            $itemIds = $request->validated('item_ids');

            $cartItems = $cart->items()
                ->whereIn('id', $itemIds)
                ->with(['product', 'sku'])
                ->get();

            if ($cartItems->isEmpty()) {
                return ApiResponse::error('未找到有效的购物车商品');
            }

            // 批量取身份折扣，下单按折后价成交
            $discountService = service(ProductDiscountService::class);
            $percentMap = $discountService->percentForProducts(Auth::user(), $cartItems->map(fn ($item) => $item->product)->unique('id')->values());

            $items = $cartItems->map(function ($item) use ($discountService, $percentMap) {
                $price = isset($percentMap[$item->product_id])
                    ? $discountService->applyPercent($item->sku->getOrderablePrice(), $percentMap[$item->product_id])
                    : null;

                return OrderItemDto::make($item->sku, $item->qty, price: $price);
            })->all();

            $orders = service(OrderService::class)
                ->createOrders(
                    user: Auth::user(),
                    items: $items,
                    fulfillmentType: FulfillmentType::from($request->safe()->string('fulfillment_type')),
                    address: $request->filled('address_id') ? $request->safe()->integer('address_id') : null,
                    pickupPointId: $request->safe()->integer('pickup_point_id'),
                    couponUserId: $request->safe()->integer('coupon_user_id') ?: null
                );

            // 清理已下单的购物车商品
            $cart->items()->whereIn('id', $itemIds)->delete();

            return ApiResponse::created(OrderCreatedResource::collection($orders));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        } finally {
            $lock->release();
        }
    }

    /**
     * 更新购物车商品数量
     *
     * @param  UpdateCartItemRequest  $request  更新购物车商品请求
     * @param  CartItem  $item  购物车商品
     *
     * @return JsonResponse 购物车详情
     */
    public function update(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        try {
            if ($item->cart->user_id !== Auth::id()) {
                return ApiResponse::forbidden();
            }

            $this->cartService->updateItemQty($item, (int) $request->validated('qty'));

            $item->cart->load(['items.product.storeConfigure', 'items.sku']);

            return ApiResponse::success(CartResource::make($item->cart), '更新成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 删除购物车商品
     *
     * @param  CartItem  $item  购物车商品
     *
     * @return JsonResponse 购物车详情
     */
    public function remove(CartItem $item): JsonResponse
    {
        try {
            if ($item->cart->user_id !== Auth::id()) {
                return ApiResponse::forbidden();
            }

            $this->cartService->removeItem($item);

            $item->cart->load(['items.product.storeConfigure', 'items.sku']);

            return ApiResponse::success(CartResource::make($item->cart), '删除成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 清空购物车
     *
     * @return JsonResponse 购物车详情
     */
    public function clear(): JsonResponse
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user());

            $this->cartService->clearCart($cart);

            // 清空后直接返回，无需重新加载关联
            return ApiResponse::success(CartResource::make($cart->fresh()), '购物车已清空');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
