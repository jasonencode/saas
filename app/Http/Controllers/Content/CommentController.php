<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\CommentCollection;
use App\Http\Resources\Contents\CommentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Comment;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * 获取内容评论列表
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
            ->paginate(min($request->integer('per_page', 15), 50));

        return ApiResponse::success(CommentCollection::make($comments));
    }

    /**
     * 对内容发表评论
     */
    public function store(Request $request, Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return ApiResponse::notFound('内容不存在');
        }

        $validated = $request->validate([
            'content' => 'required_without:pictures|string|max:2000',
            'star' => 'nullable|integer|min:1|max:5',
            'pictures' => 'nullable|array',
            'pictures.*' => 'string|max:500',
        ]);

        /** @var Comment $comment */
        $comment = $content->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'] ?? null,
            'star' => $validated['star'] ?? 0,
            'pictures' => $validated['pictures'] ?? [],
            'status' => true,
        ]);

        $comment->load(['user.profile']);

        return ApiResponse::created(CommentResource::make($comment));
    }
}
