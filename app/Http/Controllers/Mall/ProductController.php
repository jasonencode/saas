<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Mall\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mall\CommentRequest;
use App\Http\Resources\Content\CommentCollection;
use App\Http\Resources\Content\CommentResource;
use App\Http\Resources\Mall\ProductCollection;
use App\Http\Resources\Mall\ProductListItemResource;
use App\Http\Resources\Mall\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Comment;
use App\Models\Mall\Product;
use App\Services\Mall\RecommendationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * 获取商品列表
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::ofUp()
            ->with(['brand', 'category', 'storeConfigure', 'tags'])
            ->withSum('skus', 'sale')
            ->when($request->input('tenant_id'), function (Builder $builder, int $tenantId) {
                $builder->where('tenant_id', $tenantId);
            })
            ->when($request->input('name'), function (Builder $builder, string $name) {
                $builder->search('name', $name);
            })
            ->when($request->input('category_id'), function (Builder $builder, int $categoryId) {
                $builder->where('category_id', $categoryId);
            })
            ->when($request->input('brand_id'), function (Builder $builder, int $brandId) {
                $builder->where('brand_id', $brandId);
            })
            ->when($request->input('tag_id'), function (Builder $builder, int $tagId) {
                $builder->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
            })
            ->when($request->input('min_price'), function (Builder $builder, string $minPrice) {
                $builder->whereHas('skus', fn ($q) => $q->where('price', '>=', $minPrice));
            })
            ->when($request->input('max_price'), function (Builder $builder, string $maxPrice) {
                $builder->whereHas('skus', fn ($q) => $q->where('price', '<=', $maxPrice));
            })
            ->when($request->input('sort'), function (Builder $builder, string $sort) {
                $builder->orderByMatch($sort);
            }, function (Builder $builder) {
                $builder->latest();
            })
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(ProductCollection::make($products));
    }

    /**
     * 获取推荐商品
     *
     * 不传 sort 时走推荐算法（热度 + 新鲜度 + 个性化 + 品牌多样性，登录用户获得个性化加分）；
     * 传入 sort 时按指定排序确定性选取（sales_desc/newest 等，不评分、不做品牌限流）。
     *
     * @param  Request  $request  请求
     *
     * @return JsonResponse 推荐商品列表
     */
    public function recommends(Request $request): JsonResponse
    {
        $products = $this->recommendationService->recommend(
            Auth::user(),
            min($request->integer('limit', 10), 20),
            $request->input('sort'),
        );

        return ApiResponse::success(ProductListItemResource::collection($products));
    }

    /**
     * 获取商品详情
     *
     * @param  Product  $product  商品
     *
     * @return JsonResponse 商品详情
     */
    public function show(Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        $product->load(['skus', 'brand', 'category', 'storeConfigure', 'tags']);

        return ApiResponse::success(ProductResource::make($product));
    }

    /**
     * 评价商品
     *
     * @param  CommentRequest  $request  评价请求
     * @param  Product  $product  商品
     *
     * @return JsonResponse 评价结果
     */
    public function comment(CommentRequest $request, Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        // 检查是否已评价过
        $exists = Comment::where('user_id', Auth::id())
            ->where('commentable_type', $product->getMorphClass())
            ->where('commentable_id', $product->getKey())
            ->exists();

        if ($exists) {
            return ApiResponse::error('该商品已评价');
        }

        try {
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => $product->getMorphClass(),
                'commentable_id' => $product->getKey(),
                'star' => $request->validated('star'),
                'content' => $request->validated('content'),
                'pictures' => $request->validated('pictures', []),
                'status' => true,
            ]);

            return ApiResponse::created([
                'comment_id' => $comment->id,
                'star' => $comment->star,
                'content' => $comment->content,
                'created_at' => $comment->created_at?->toDateTimeString(),
            ], '评价成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 获取商品评价列表
     *
     * @param  Request  $request  请求
     * @param  Product  $product  商品
     *
     * @return JsonResponse 评价列表
     */
    public function comments(Request $request, Product $product): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        $comments = $product->comments()
            ->ofEnabled()
            ->with(['user.profile'])
            ->latest()
            ->paginate(min($request->integer('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(CommentCollection::make($comments));
    }

    /**
     * 获取商品评价详情
     *
     * @param  Product  $product  商品
     * @param  int  $commentId  评价 ID
     *
     * @return JsonResponse 评价详情
     */
    public function commentShow(Product $product, int $commentId): JsonResponse
    {
        if ($product->status !== ProductStatus::Up) {
            return ApiResponse::notFound('商品不存在');
        }

        $comment = $product->comments()
            ->ofEnabled()
            ->with(['user.profile'])
            ->find($commentId);

        if (!$comment) {
            return ApiResponse::notFound('评价不存在');
        }

        return ApiResponse::success(CommentResource::make($comment));
    }
}
