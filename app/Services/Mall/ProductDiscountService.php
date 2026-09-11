<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Models\Mall\Product;
use App\Models\Mall\ProductDiscount;
use App\Models\Mall\Sku;
use App\Models\User\User;
use Illuminate\Support\Collection;

class ProductDiscountService implements ServiceInterface
{
    /**
     * 获取用户在指定租户下的有效身份 ID（一次查询）
     *
     * @param  User  $user  用户
     * @param  int  $tenantId  租户 ID
     *
     * @return int|null 身份 ID，无有效身份时返回 null
     */
    public function identityIdFor(User $user, int $tenantId): ?int
    {
        return $user->identities()
            ->where('user_identity.tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereNull('user_identity.end_at')
                    ->orWhere('user_identity.end_at', '>', now());
            })
            // 防御性排序：entry() 保证单租户单身份，仍取最新一条兜底
            ->orderByDesc('user_identity.start_at')
            ->value('user_identity.identity_id');
    }

    /**
     * 获取用户对商品的折后 SKU 单价（无折扣时返回原价）
     *
     * @param  User  $user  用户
     * @param  Sku  $sku  商品规格
     *
     * @return string 折后价（保留两位小数）
     */
    public function priceFor(User $user, Sku $sku): string
    {
        $percent = $this->percentFor($user, $sku->product);

        if ($percent === null) {
            return $sku->getOrderablePrice();
        }

        return $this->applyPercent($sku->getOrderablePrice(), $percent);
    }

    /**
     * 获取用户对商品的身份折扣百分比
     *
     * @param  User  $user  用户
     * @param  Product  $product  商品
     *
     * @return int|null 折扣百分比，无折扣时返回 null
     */
    public function percentFor(User $user, Product $product): ?int
    {
        $identityId = $this->identityIdFor($user, $product->tenant_id);

        if ($identityId === null) {
            return null;
        }

        return $product->discounts()
            ->wherePivot('identity_id', $identityId)
            ->value('product_discounts.percent');
    }

    /**
     * 批量获取用户对多个商品的折扣百分比（防 N+1）
     *
     * 购物车/结算场景商品可能跨租户：一次查询用户在各租户的有效身份，
     * 一次查询命中的折扣，内存中按「商品 → 租户 → 身份」匹配。
     *
     * @param  User  $user  用户
     * @param  Collection<int, Product>  $products  商品集合
     *
     * @return array<int, int> 商品 ID => 折扣百分比（仅含有折扣的商品）
     */
    public function percentForProducts(User $user, Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $tenantIds = $products->pluck('tenant_id')->unique()->all();

        // 一次查询用户在各租户的有效身份：tenant_id => identity_id
        $identityMap = $user->identities()
            ->whereIn('user_identity.tenant_id', $tenantIds)
            ->where(function ($query) {
                $query->whereNull('user_identity.end_at')
                    ->orWhere('user_identity.end_at', '>', now());
            })
            ->orderByDesc('user_identity.start_at')
            ->get(['identities.id', 'user_identity.tenant_id'])
            ->pluck('id', 'pivot.tenant_id');

        if ($identityMap->isEmpty()) {
            return [];
        }

        // 商品 → 其所属租户对应的身份 ID
        $productIdentityMap = $products
            ->mapWithKeys(fn (Product $product) => [
                $product->getKey() => $identityMap->get($product->tenant_id),
            ])
            ->filter();

        if ($productIdentityMap->isEmpty()) {
            return [];
        }

        // 一次查询命中的 (product_id, identity_id) 折扣
        return ProductDiscount::query()
            ->whereIn('product_id', $productIdentityMap->keys()->all())
            ->whereIn('identity_id', $productIdentityMap->unique()->values()->all())
            ->get(['product_id', 'identity_id', 'percent'])
            ->filter(fn (ProductDiscount $discount) => $productIdentityMap->get($discount->product_id) === (int) $discount->identity_id)
            ->mapWithKeys(fn (ProductDiscount $discount) => [$discount->product_id => (int) $discount->percent])
            ->all();
    }

    /**
     * 应用百分比折扣（bcmath，四舍五入保留 2 位）
     *
     * @param  string  $price  原价
     * @param  int  $percent  折扣百分比（80 表示打 8 折）
     *
     * @return string 折后价（保留两位小数）
     */
    public function applyPercent(string $price, int $percent): string
    {
        // price × percent / 100，先算 4 位再四舍五入到分
        $raw = bcdiv(bcmul($price, (string) $percent, 6), '100', 4);

        return number_format((float) $raw, 2, '.', '');
    }
}
