<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Cart;
use App\Models\Mall\CartItem;
use App\Models\Mall\Sku;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CartService implements ServiceInterface
{
    /**
     * 更新购物车商品数量
     *
     * @throws RuntimeException
     */
    public function updateItemQty(CartItem $item, int $qty): CartItem
    {
        if ($qty < 1 || $qty > 9999) {
            throw new RuntimeException('商品数量必须在 1-9999 之间');
        }

        if ($item->product && $item->product->status !== ProductStatus::Up) {
            throw new RuntimeException('商品已下架');
        }

        if ($item->sku && $item->sku->stock < $qty) {
            throw new RuntimeException('商品库存不足');
        }

        $item->update(['qty' => $qty]);

        return $item;
    }

    /**
     * 删除购物车商品
     */
    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * 清空购物车
     */
    public function clearCart(Cart $cart): void
    {
        $cart->clear();
    }

    /**
     * 验证购物车商品有效性
     *
     * @return array<string, mixed>
     */
    public function validateCartItems(Cart $cart): array
    {
        $validItems = [];
        $invalidItems = [];

        foreach ($cart->items as $item) {
            if ($item->isAvailable()) {
                $validItems[] = $item;
            } else {
                $invalidItems[] = [
                    'item_id' => $item->id,
                    'reason' => $this->getInvalidReason($item),
                ];
            }
        }

        return [
            'valid' => $validItems,
            'invalid' => $invalidItems,
            'has_invalid' => !empty($invalidItems),
        ];
    }

    /**
     * 获取商品无效原因
     */
    private function getInvalidReason(CartItem $item): string
    {
        if (!$item->product) {
            return '商品不存在';
        }

        if ($item->product->status !== ProductStatus::Up) {
            return '商品已下架';
        }

        if (!$item->sku) {
            return '规格不存在';
        }

        if ($item->sku->stock < $item->qty) {
            return '库存不足';
        }

        return '商品不可用';
    }

    /**
     * 合并会话购物车到用户购物车
     */
    public function mergeSessionCart(User $user, string $sessionId): Cart
    {
        $sessionCart = Cart::where('session_id', $sessionId)
            ->where('tenant_id', $user->tenant_id ?? null)
            ->first();

        if (!$sessionCart) {
            return $this->getOrCreateCart($user);
        }

        return DB::transaction(function () use ($user, $sessionCart) {
            $userCart = $this->getOrCreateCart($user);

            foreach ($sessionCart->items as $sessionItem) {
                try {
                    $this->addItem($userCart, $sessionItem->sku, $sessionItem->qty);
                } catch (RuntimeException) {
                    continue;
                }
            }

            $sessionCart->delete();

            return $userCart;
        });
    }

    /**
     * 获取或创建购物车
     */
    public function getOrCreateCart(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)
            ->whereNull('expired_at')
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id ?? null,
                'status' => true,
            ]);
        }

        return $cart;
    }

    /**
     * 添加商品到购物车
     *
     * @throws RuntimeException
     */
    public function addItem(Cart $cart, Sku $sku, int $qty): CartItem
    {
        // 检查商品是否上架
        if ($sku->product && $sku->product->status !== ProductStatus::Up) {
            throw new RuntimeException('商品已下架');
        }

        if ($sku->stock < $qty) {
            throw new RuntimeException('商品库存不足');
        }

        $item = $cart->items()
            ->where('sku_id', $sku->id)
            ->first();

        if ($item) {
            $newQty = $item->qty + $qty;

            if ($newQty > 9999) {
                throw new RuntimeException('购买数量超过限制');
            }

            $item->update(['qty' => $newQty]);
        } else {
            $item = $cart->items()->create([
                'product_id' => $sku->product_id,
                'sku_id' => $sku->id,
                'qty' => $qty,
                'price_at_add' => $sku->price,
            ]);
        }

        return $item;
    }
}
