<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class CartService implements ServiceInterface
{
    /**
     * 获取或创建购物车
     *
     * @param  User  $user  用户对象
     * @return Cart 购物车对象
     */
    public function getOrCreateCart(User $user): Cart
    {
        $cart = Cart::query()
            ->where('user_id', $user->id)
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
     * @param  Cart  $cart  购物车对象
     * @param  Sku  $sku  SKU 对象
     * @param  int  $qty  数量
     * @return CartItem 购物车商品项
     *
     * @throws RuntimeException 当库存不足时抛出
     */
    public function addItem(Cart $cart, Sku $sku, int $qty): CartItem
    {
        // 检查库存
        if ($sku->stock < $qty) {
            throw new RuntimeException('商品库存不足');
        }

        // 查找是否已存在该 SKU
        $item = $cart->items()
            ->where('sku_id', $sku->id)
            ->first();

        if ($item) {
            // 更新数量
            $newQty = $item->qty + $qty;
            if ($newQty > 9999) {
                throw new RuntimeException('购买数量超过限制');
            }
            $item->update(['qty' => $newQty]);
        } else {
            // 新增记录
            $item = $cart->items()->create([
                'sku_id' => $sku->id,
                'qty' => $qty,
                'price_at_add' => $sku->price,
                'selected' => true,
            ]);
        }

        return $item;
    }

    /**
     * 更新购物车商品数量
     *
     * @param  CartItem  $item  购物车商品项
     * @param  int  $qty  新数量
     * @return CartItem 更新后的商品项
     *
     * @throws RuntimeException 当数量无效或库存不足时抛出
     */
    public function updateItemQty(CartItem $item, int $qty): CartItem
    {
        if ($qty < 1 || $qty > 9999) {
            throw new RuntimeException('商品数量必须在 1-9999 之间');
        }

        // 检查库存
        if ($item->sku && $item->sku->stock < $qty) {
            throw new RuntimeException('商品库存不足');
        }

        $item->update(['qty' => $qty]);

        return $item;
    }

    /**
     * 切换商品选中状态
     *
     * @param  CartItem  $item  购物车商品项
     * @return CartItem 更新后的商品项
     */
    public function toggleItemSelected(CartItem $item): CartItem
    {
        $item->update([
            'selected' => !$item->selected,
        ]);

        return $item;
    }

    /**
     * 删除购物车商品
     *
     * @param  CartItem  $item  购物车商品项
     */
    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * 清空购物车
     *
     * @param  Cart  $cart  购物车对象
     */
    public function clearCart(Cart $cart): void
    {
        $cart->clear();
    }

    /**
     * 批量选中商品
     *
     * @param  Cart  $cart  购物车对象
     * @param  array<int>  $itemIds  商品项 ID 数组
     * @return int 更新的记录数
     */
    public function selectItems(Cart $cart, array $itemIds): int
    {
        return $cart->items()
            ->whereIn('id', $itemIds)
            ->update(['selected' => true]);
    }

    /**
     * 批量取消选中商品
     *
     * @param  Cart  $cart  购物车对象
     * @param  array<int>  $itemIds  商品项 ID 数组
     * @return int 更新的记录数
     */
    public function deselectItems(Cart $cart, array $itemIds): int
    {
        return $cart->items()
            ->whereIn('id', $itemIds)
            ->update(['selected' => false]);
    }

    /**
     * 获取选中的商品列表
     *
     * @param  Cart  $cart  购物车对象
     * @return Collection<int, CartItem>
     */
    public function getSelectedItems(Cart $cart): Collection
    {
        return $cart->items()
            ->where('selected', true)
            ->with(['product', 'sku'])
            ->get();
    }

    /**
     * 计算选中商品的总金额
     *
     * @param  Cart  $cart  购物车对象
     * @return float 总金额
     */
    public function calculateSelectedTotal(Cart $cart): float
    {
        $total = $cart->items()
            ->where('selected', true)
            ->get()
            ->reduce(function ($carry, CartItem $item) {
                return bcadd($carry, $item->sub_total, 2);
            }, '0.00');

        return (float) $total;
    }

    /**
     * 验证购物车商品有效性
     *
     * @param  Cart  $cart  购物车对象
     * @return array<string, mixed> 验证结果，包含有效和无效商品列表
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
     *
     * @param  CartItem  $item  购物车商品项
     * @return string 无效原因
     */
    private function getInvalidReason(CartItem $item): string
    {
        if (!$item->product) {
            return '商品不存在';
        }

        if (!$item->product->status) {
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
     *
     * @param  User  $user  用户对象
     * @param  string  $sessionId  会话 ID
     * @return Cart 合并后的购物车
     */
    public function mergeSessionCart(User $user, string $sessionId): Cart
    {
        // 获取会话购物车
        $sessionCart = Cart::query()
            ->where('session_id', $sessionId)
            ->where('tenant_id', $user->tenant_id ?? null)
            ->first();

        if (!$sessionCart) {
            return $this->getOrCreateCart($user);
        }

        // 获取或创建用户购物车
        $userCart = $this->getOrCreateCart($user);

        // 合并商品
        foreach ($sessionCart->items as $sessionItem) {
            try {
                $this->addItem($userCart, $sessionItem->sku, $sessionItem->qty);
            } catch (RuntimeException) {
                // 跳过无法合并的商品（如库存不足）
                continue;
            }
        }

        // 删除会话购物车
        $sessionCart->delete();

        return $userCart;
    }
}
