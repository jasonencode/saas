<?php

namespace Tests\Unit\Services\Mall;

use App\Models\Mall\Product;
use App\Services\Mall\RecommendationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    /**
     * 调用私有评分算法（不触发数据库查询）
     *
     * @param  Collection<int, Product>  $candidates  候选商品
     * @param  int  $limit  返回条数
     *
     * @return Collection<int, Product> 推荐结果
     */
    private function byAlgorithm(Collection $candidates, int $limit): Collection
    {
        $method = new ReflectionMethod(RecommendationService::class, 'byAlgorithm');

        return $method->invoke($this->app->make(RecommendationService::class), $candidates, null, $limit);
    }

    /**
     * 构造离线商品实例
     *
     * @param  int  $id  商品 ID
     * @param  int  $categoryId  分类 ID
     * @param  int  $sales  SKU 销量合计
     * @param  int  $views  浏览量
     * @param  int  $daysAgo  上架天数
     *
     * @return Product 商品实例
     */
    private function product(int $id, int $categoryId, int $sales, int $views, int $daysAgo): Product
    {
        $product = new Product([
            'category_id' => $categoryId,
            'views' => $views,
            'skus_sale_sum' => $sales,
            'created_at' => Carbon::now()->subDays($daysAgo),
        ]);
        $product->id = $id;

        return $product;
    }

    #[Test]
    public function test_empty_candidates_returns_empty(): void
    {
        $result = $this->byAlgorithm(collect([]), 10);

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function test_high_sales_beats_low_sales(): void
    {
        $hot = $this->product(1, 1, 10000, 0, 30);
        $cold = $this->product(2, 2, 0, 0, 30);

        $result = $this->byAlgorithm(collect([$cold, $hot]), 2);

        $this->assertSame([1, 2], $result->pluck('id')->all());
    }

    #[Test]
    public function test_freshness_can_outrank_slightly_higher_sales_in_realistic_pool(): void
    {
        // 热度分是池内 min-max 归一化：加入高销量锚点，让 old/fresh 的销量差只占极小一段，
        // 此时新鲜度（权重 0.3）足以翻盘——还原真实 200 候选池的场景
        $anchor = $this->product(3, 3, 1000, 0, 30);
        $old = $this->product(1, 1, 20, 0, 60);
        $fresh = $this->product(2, 2, 18, 0, 0);

        $result = $this->byAlgorithm(collect([$anchor, $old, $fresh]), 10);

        // 新鲜品（id=2）应排在更老的品（id=1）之前
        $this->assertLessThan(
            $result->search(fn (Product $p) => $p->id === 1),
            $result->search(fn (Product $p) => $p->id === 2),
        );
    }

    #[Test]
    public function test_limit_caps_result_count(): void
    {
        $products = collect(range(1, 10))->map(
            fn (int $i) => $this->product($i, $i, $i, 0, 0)
        );

        $result = $this->byAlgorithm($products, 3);

        $this->assertCount(3, $result);
    }

    #[Test]
    public function test_result_varies_across_calls_with_top_band_shuffle(): void
    {
        // 12 个热度相近的商品，limit=4 → Top 带 12 名内随机抽 4
        $products = collect(range(1, 12))->map(
            fn (int $i) => $this->product($i, $i, 100 + $i, 0, 0)
        );

        $first = $this->byAlgorithm($products, 4)->pluck('id')->all();
        $this->assertCount(4, $first);

        // 多轮调用，结果序列应出现变化（Top 带内随机）
        $seen = [implode(',', $first)];
        for ($i = 0; $i < 19; $i++) {
            $seen[] = implode(',', $this->byAlgorithm($products, 4)->pluck('id')->all());
        }

        $this->assertGreaterThan(1, count(array_unique($seen)), '多轮推荐结果完全相同，随机性未生效');
    }

    #[Test]
    public function test_result_is_sorted_by_score_desc(): void
    {
        // 销量差异明显：Top 带随机选中任意 5 个，输出必须按分数降序
        $products = collect(range(1, 8))->map(
            fn (int $i) => $this->product($i, $i, $i * 50, 0, 0)
        );

        $result = $this->byAlgorithm($products, 5);

        $this->assertCount(5, $result);

        // 同池、同新鲜度、无个性化时，分数降序 = 销量降序
        $sales = $result->map(fn (Product $p) => $p->skus_sale_sum)->all();
        $this->assertSame($sales, collect($sales)->sortDesc()->all());
    }
}
