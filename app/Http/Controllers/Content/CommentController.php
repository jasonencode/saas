<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreCommentRequest;
use App\Http\Resources\Content\CommentCollection;
use App\Http\Resources\Content\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Comment;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * 获取内容评论列表
     *
     * @param  Request  $request  请求
     * @param  Content  $content  内容
     *
     * @return JsonResponse 评论列表
     */
    public function index(Request $request, Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return ApiResponse::notFound('内容不存在');
        }

        $comments = $content->comments()
            ->ofEnabled()
            ->with(['user.profile'])
            ->latest()
            ->paginate(min($request->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(CommentCollection::make($comments));
    }

    /**
     * 对内容发表评论
     *
     * @param  StoreCommentRequest  $request  评论请求
     * @param  Content  $content  内容
     *
     * @return JsonResponse 创建的评论
     */
    public function store(StoreCommentRequest $request, Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return ApiResponse::notFound('内容不存在');
        }

        /** @var Comment $comment */
        $comment = $content->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->safe()->string('content'),
            'star' => $request->safe()->integer('star', 0),
            'pictures' => $request->safe()->array('pictures', []),
            'status' => true,
        ]);

        $comment->load(['user.profile']);

        return ApiResponse::created(CommentResource::make($comment));
    }
}
