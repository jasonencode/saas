<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Overtrue\LaravelFavorite\Favorite;

class RecommendationService implements ServiceInterface
{
    /**
     * 获取推荐商品
     *
     * 不传 sort 时使用推荐算法（热度 + 新鲜度 + 个性化 + 品牌多样性）；
     * 传入 sort 时按指定排序确定性选取（不评分、不做品牌限流）。
     *
     * @param  User|null  $user  当前用户（用于个性化加分，游客传 null）
     * @param  int  $limit  返回条数
     * @param  string|null  $sort  选取方式（sales_desc/newest 等，空为推荐算法）
     *
     * @return Collection<int, Product> 推荐商品
     */
    public function recommend(?User $user, int $limit, ?string $sort = null): Collection
    {
        if ($sort !== null) {
            return $this->candidates($sort)->take($limit)->values();
        }

        return $this->byAlgorithm($this->randomPool(), $user, $limit);
    }

    /**
     * 随机候选池
     *
     * 从手动排序前 candidate_cap 条中随机抽取 candidate_pool 条参与评分，
     * 引入请求间多样性；候选总数不足 cap 时全量参与
     *
     * @return Collection<int, Product> 候选商品
     */
    private function randomPool(): Collection
    {
        $cap = (int) config('custom.mall.recommend.candidate_cap', 500);
        $pool = (int) config('custom.mall.recommend.candidate_pool', 200);

        return Product::ofUp()
            ->whereHas('skus', fn (Builder $query) => $query->where('stock', '>', 0))
            ->bySort()
            ->with(['brand', 'category', 'storeConfigure'])
            ->withSum('skus', 'sale')
            ->limit($cap)
            ->inRandomOrder()
            ->limit($pool)
            ->get();
    }

    /**
     * 候选池：上架且至少一个 SKU 有货
     *
     * 池内排序：指定 sort 按其排序，否则按手动排序（运营排序优先级最高）
     *
     * @param  string|null  $sort  选取方式
     *
     * @return Collection<int, Product> 候选商品
     */
    private function candidates(?string $sort): Collection
    {
        return Product::ofUp()
            ->whereHas('skus', fn (Builder $query) => $query->where('stock', '>', 0))
            ->when(
                $sort,
                fn (Builder $query, string $s) => $query->orderByMatch($s),
                fn (Builder $query) => $query->bySort(),
            )
            ->with(['brand', 'category', 'storeConfigure'])
            ->withSum('skus', 'sale')
            ->limit((int) config('custom.mall.recommend.candidate_pool', 200))
            ->get();
    }

    /**
     * 推荐算法评分
     *
     * score = 热度分 × 0.5 + 新鲜度分 × 0.3 + 个性化分 × 0.2（权重见 config）
     * - 热度分：log10(1+销量) + 0.5×log10(1+浏览量)，候选池内归一化，log 抑制爆款垄断
     * - 新鲜度分：exp(-上架天数/衰减周期)，新品冷启动保护
     * - 个性化分：命中用户偏好品牌/分类（近 90 天订单 + 收藏）得 0~1 分
     * - 品牌多样性：结果中每个品牌最多 N 个
     *
     * @param  Collection<int, Product>  $candidates  候选商品
     * @param  User|null  $user  当前用户
     * @param  int  $limit  返回条数
     *
     * @return Collection<int, Product> 推荐商品
     */
    private function byAlgorithm(Collection $candidates, ?User $user, int $limit): Collection
    {
        if ($candidates->isEmpty()) {
            return collect([]);
        }

        $weights = config('custom.mall.recommend.weight', []);
        $freshnessDays = (int) config('custom.mall.recommend.freshness_days', 7);
        $brandMax = (int) config('custom.mall.recommend.brand_max', 2);

        [$brands, $categories] = $this->preferences($user);
        $brandMaxWeight = max(array_merge([0.0001], array_values($brands)));
        $categoryMaxWeight = max(array_merge([0.0001], array_values($categories)));

        // 热度分（候选池内 min-max 归一化，按商品 ID 作键）
        $pops = $candidates->mapWithKeys(
            fn (Product $product) => [$product->id => log10(1 + (int) $product->skus_sale_sum) + 0.5 * log10(1 + (int) $product->views)]
        );
        $popMin = $pops->min();
        $popRange = $pops->max() - $popMin;

        // 逐项评分（键与商品 ID 对齐）
        $scores = $candidates->mapWithKeys(
            function (Product $product) use ($pops, $popMin, $popRange, $weights, $freshnessDays, $brands, $categories, $brandMaxWeight, $categoryMaxWeight): array {
                $popularity = $popRange > 0 ? ($pops[$product->id] - $popMin) / $popRange : 1.0;
                $freshness = exp(-max(0, $product->created_at->diffInDays(now())) / max(1, $freshnessDays));
                $personal = max(
                    isset($brands[$product->brand_id]) ? $brands[$product->brand_id] / $brandMaxWeight : 0.0,
                    isset($categories[$product->category_id]) ? $categories[$product->category_id] / $categoryMaxWeight : 0.0,
                );

                return [
                    $product->id => (float) ($weights['popularity'] ?? 0.5) * $popularity
                        + (float) ($weights['freshness'] ?? 0.3) * $freshness
                        + (float) ($weights['personal'] ?? 0.2) * $personal,
                ];
            }
        );

        // 按分数降序得到商品 ID 序列
        $byId = $candidates->keyBy('id');
        $ranked = $scores
            ->sortByDesc(fn (float $score): float => $score, SORT_NUMERIC)
            ->keys();

        // 品牌多样性限流：每个品牌最多 N 个
        $brandCount = [];
        $result = [];

        foreach ($ranked as $productId) {
            if (count($result) >= $limit) {
                break;
            }

            $product = $byId[$productId];
            $brandCount[$product->brand_id] = ($brandCount[$product->brand_id] ?? 0) + 1;
            if ($brandCount[$product->brand_id] > $brandMax) {
                continue;
            }

            $result[] = $product;
        }

        return collect($result);
    }

    /**
     * 提取用户品牌/分类偏好权重
     *
     * 信号源：近 N 天有效订单（按购买数量加权）+ 收藏（固定半权）
     *
     * @param  User|null  $user  当前用户
     *
     * @return array{0: array<int, float>, 1: array<int, float>} [品牌 => 权重, 分类 => 权重]
     */
    private function preferences(?User $user): array
    {
        if ($user === null) {
            return [[], []];
        }

        $days = (int) config('custom.mall.recommend.personal_days', 90);
        $brands = [];
        $categories = [];

        // 近 N 天有效订单（排除待付款/已取消），按购买数量加权
        OrderItem::where('orderable_type', Product::class)
            ->whereHas('order', function (Builder $query) use ($user, $days) {
                $query
                    ->where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->whereNotIn('status', [OrderStatus::Pending, OrderStatus::Canceled]);
            })
            ->with('orderable')
            ->limit(500)
            ->each(function (OrderItem $item) use (&$brands, &$categories) {
                $product = $item->orderable;
                if (!$product instanceof Product) {
                    return;
                }

                $brands[$product->brand_id] = ($brands[$product->brand_id] ?? 0) + (float) $item->qty;
                $categories[$product->category_id] = ($categories[$product->category_id] ?? 0) + (float) $item->qty;
            });

        // 收藏商品，品牌/分类各加半权
        $favoriteIds = Favorite::where('user_id', $user->id)
            ->where('favoriteable_type', Product::class)
            ->pluck('favoriteable_id');

        if ($favoriteIds->isNotEmpty()) {
            Product::whereIn('id', $favoriteIds)
                ->with(['brand', 'category'])
                ->each(function (Product $product) use (&$brands, &$categories) {
                    $brands[$product->brand_id] = ($brands[$product->brand_id] ?? 0) + 0.5;
                    $categories[$product->category_id] = ($categories[$product->category_id] ?? 0) + 0.5;
                });
        }

        return [$this->topWeights($brands, 3), $this->topWeights($categories, 3)];
    }

    /**
     * 取权重最高的前 N 项
     *
     * @param  array<int, float>  $weights  id => 权重
     * @param  int  $count  保留数量
     *
     * @return array<int, float> 降序排列的权重
     */
    private function topWeights(array $weights, int $count): array
    {
        arsort($weights);

        return array_slice($weights, 0, $count, true);
    }
}
