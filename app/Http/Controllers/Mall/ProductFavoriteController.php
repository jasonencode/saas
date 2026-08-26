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
        $products = Product::ofUp()
            ->whereHas('favoriters', fn ($q) => $q->where('user_id', Auth::id()))
            ->with(['brand', 'storeConfigure'])
            ->withSum('skus', 'sale')
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
            ], $isFavorited ? '收藏成功' : '已取消收藏');
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
