<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProductFavoriteController extends Controller
{
    /**
     * 获取用户收藏的商品列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 收藏商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $products = Product::ofUp()
            ->whereHas('favoriters', fn ($q) => $q->where('user_id', $userId))
            ->with(['brand', 'storeConfigure'])
            ->withSum('skus', 'sale')
            // 子查询取当前用户对该商品的最新收藏时间，limit 1 避免重复收藏行影响分页
            ->selectSub(function ($q) use ($userId) {
                $q->select('created_at')
                    ->from(config('favorite.favorites_table'))
                    ->whereColumn('favoriteable_id', 'products.id')
                    ->where('favoriteable_type', Product::class)
                    ->where(config('favorite.user_foreign_key'), $userId)
                    ->orderByDesc('created_at')
                    ->limit(1);
            }, 'favorited_at')
            ->orderByDesc('favorited_at')
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ProductCollection::make($products));
    }

    /**
     * 收藏/取消收藏商品（切换）
     *
     * @param  Product  $product  商品
     *
     * @return JsonResponse 操作结果
     */
    public function toggle(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        try {
            Auth::user()->toggleFavorite($product);

            $isFavorited = Auth::user()->hasFavorited($product);

            return ApiResponse::success([
                'is_favorited' => $isFavorited,
            ]);
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 检查商品是否已收藏
     *
     * @param  Product  $product  商品
     *
     * @return JsonResponse 收藏状态
     */
    public function check(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        return ApiResponse::success([
            'is_favorited' => Auth::user()->hasFavorited($product),
        ]);
    }
}
