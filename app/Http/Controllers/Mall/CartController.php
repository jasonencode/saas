<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\StoreCartItemRequest;
use App\Http\Requests\Mall\UpdateCartItemRequest;
use App\Http\Resources\Mall\CartResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\CartItem;
use App\Models\Mall\Sku;
use App\Services\Mall\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    )
    {
    }

    /**
     * 获取购物车列表
     */
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());

        $cart->load(['items.product', 'items.sku']);

        return ApiResponse::success(new CartResource($cart));
    }

    /**
     * 添加商品到购物车
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
     * 更新购物车商品数量
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
     * 选中/取消选中商品
     */
    public function toggle(CartItem $item): JsonResponse
    {
        try {
            if ($item->cart->user_id !== Auth::id()) {
                return ApiResponse::forbidden();
            }

            $this->cartService->toggleItemSelected($item);

            $item->cart->load(['items.product', 'items.sku']);

            return ApiResponse::success(new CartResource($item->cart));
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 删除购物车商品
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
     */
    public function clear(): JsonResponse
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user());

            $this->cartService->clearCart($cart);

            $cart->load(['items.product', 'items.sku']);

            return ApiResponse::success(new CartResource($cart), '购物车已清空');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
