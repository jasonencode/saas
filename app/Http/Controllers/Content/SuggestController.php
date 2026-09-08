<?php

namespace App\Http\Controllers\Content;

use App\Enums\Content\SuggestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\IndexSuggestRequest;
use App\Http\Requests\Content\StoreSuggestMessageRequest;
use App\Http\Requests\Content\StoreSuggestRequest;
use App\Http\Resources\Content\SuggestCollection;
use App\Http\Resources\Content\SuggestMessageCollection;
use App\Http\Resources\Content\SuggestMessageResource;
use App\Http\Resources\Content\SuggestResource;
use App\Http\Resources\EnumResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Suggest;
use App\Services\Content\SuggestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestController extends Controller
{
    /**
     * 提交反馈
     *
     * @param  StoreSuggestRequest  $request  请求
     *
     * @return JsonResponse 创建的反馈
     */
    public function store(StoreSuggestRequest $request): JsonResponse
    {
        $suggest = service(SuggestService::class)->store(
            $request->user(),
            $request->safe()->string('type'),
            $request->safe()->string('contact'),
            $request->safe()->string('content'),
        );

        return ApiResponse::created(SuggestResource::make($suggest));
    }

    /**
     * 我的反馈列表
     *
     * @param  IndexSuggestRequest  $request  请求
     *
     * @return JsonResponse 反馈列表
     */
    public function index(IndexSuggestRequest $request): JsonResponse
    {
        $suggests = Suggest::where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->safe()->string('status')))
            ->with('messages')
            ->latest('updated_at')
            ->paginate(min($request->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(SuggestCollection::make($suggests));
    }

    /**
     * 反馈详情（对话列表）
     *
     * @param  Request  $request  请求
     * @param  Suggest  $suggest  反馈
     *
     * @return JsonResponse 对话列表
     */
    public function messages(Request $request, Suggest $suggest): JsonResponse
    {
        if ($suggest->user_id !== $request->user()->id) {
            return ApiResponse::forbidden('无权访问此反馈');
        }

        $messages = $suggest->messages()
            ->with('sender')
            ->oldest('created_at')
            ->paginate(min($request->integer('per_page', 20), config('custom.pagination.max_per_page')));

        return ApiResponse::success([
            'suggest' => [
                'suggest_id' => $suggest->id,
                'type' => EnumResource::make($suggest->type),
                'status' => EnumResource::make($suggest->status),
            ],
            'messages' => SuggestMessageCollection::make($messages),
        ]);
    }

    /**
     * 追加反馈消息
     *
     * @param  StoreSuggestMessageRequest  $request  请求
     * @param  Suggest  $suggest  反馈
     *
     * @return JsonResponse 创建的消息
     */
    public function storeMessage(StoreSuggestMessageRequest $request, Suggest $suggest): JsonResponse
    {
        if ($suggest->user_id !== $request->user()->id) {
            return ApiResponse::forbidden('无权访问此反馈');
        }

        if ($suggest->status === SuggestStatus::Closed) {
            return ApiResponse::error('反馈已关闭，无法继续回复');
        }

        $message = service(SuggestService::class)->appendUserMessage(
            $suggest,
            $request->user(),
            (string) $request->safe()->string('content'),
        );

        $message->load('sender');

        return ApiResponse::created(SuggestMessageResource::make($message));
    }
}
