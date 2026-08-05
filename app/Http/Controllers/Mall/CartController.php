<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\CheckoutPreviewRequest;
use App\Http\Requests\Mall\OrderFromCartRequest;
use App\Http\Requests\Mall\StoreCartItemRequest;
use App\Http\Requests\Mall\UpdateCartItemRequest;
use App\Http\Resources\Mall\CartResource;
use App\Http\Resources\Mall\CheckoutResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\CartItem;
use App\Models\Mall\Delivery;
use App\Models\Mall\Sku;
use App\Models\User\Address;
use App\Services\Mall\CartService;
use App\Services\Mall\DeliveryService;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        $cart->load(['items.product', 'items.sku']);

        return ApiResponse::success(new CartResource($cart));
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

            $cart->load(['items.product', 'items.sku']);

            return ApiResponse::success(new CartResource($cart), '添加成功');
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

        // 计算商品总金额
        $totalAmount = $cartItems->reduce(fn ($carry, $item) => bcadd($carry, $item->sub_total, 2), '0.00');

        // 获取用户地址列表
        $addresses = Auth::user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        // 计算运费（传入地址时）
        $addressId = $request->safe()->integer('address_id');
        $address = $addressId ? Address::find($addressId) : null;
        $freight = '0.00';

        if ($address && $address->user->is(Auth::user())) {
            $deliveryService = app(DeliveryService::class);

            $groupedByDelivery = $cartItems->groupBy(fn ($item) => $item->product->delivery_id ?? 'default');

            foreach ($groupedByDelivery as $deliveryId => $groupItems) {
                $delivery = $deliveryId === 'default'
                    ? $deliveryService->getDefaultForTenant($cart->tenant_id)
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

        return ApiResponse::success(new CheckoutResource(collect([
            'items' => $cartItems,
            'addresses' => $addresses,
            'address' => $address,
            'total_amount' => $totalAmount,
            'freight' => $freight,
            'payable_amount' => bcadd($totalAmount, $freight, 2),
        ])));
    }

    /**
     * 从购物车创建订单
     *
     * @param  OrderFromCartRequest  $request  从购物车创建订单请求
     *
     * @return JsonResponse 创建的订单 ID 列表
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

            $items = $cartItems->map(fn ($item) => OrderItemDto::make($item->sku, $item->qty))->all();

            $orders = service(OrderService::class)
                ->createOrders(Auth::user(), $items, $request->safe()->integer('address_id'));

            // 清理已下单的购物车商品
            $cart->items()->whereIn('id', $itemIds)->delete();

            return ApiResponse::created($orders->pluck('id')->toArray(), '订单创建成功');
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

            $item->cart->load(['items.product', 'items.sku']);

            return ApiResponse::success(new CartResource($item->cart), '更新成功');
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

            $item->cart->load(['items.product', 'items.sku']);

            return ApiResponse::success(new CartResource($item->cart), '删除成功');
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
            return ApiResponse::success(new CartResource($cart->fresh()), '购物车已清空');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
