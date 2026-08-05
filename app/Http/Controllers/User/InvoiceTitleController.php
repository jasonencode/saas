<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\User\InvoiceTitleRequest;
use App\Http\Resources\User\InvoiceTitleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\InvoiceTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceTitleController
{
    use AuthorizesModelAccess;

    /**
     * 获取发票抬头列表
     *
     * @return JsonResponse 发票抬头列表
     */
    public function index(): JsonResponse
    {
        $titles = InvoiceTitle::ofCurrentUser()
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return ApiResponse::success(InvoiceTitleResource::collection($titles));
    }

    /**
     * 获取发票抬头详情
     *
     * @param  InvoiceTitle  $invoiceTitle  发票抬头
     *
     * @return JsonResponse 发票抬头详情
     */
    public function show(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        return ApiResponse::success(InvoiceTitleResource::make($invoiceTitle));
    }

    /**
     * 创建发票抬头
     *
     * @param  InvoiceTitleRequest  $request  发票抬头请求
     *
     * @return JsonResponse 创建的发票抬头
     */
    public function store(InvoiceTitleRequest $request): JsonResponse
    {
        $count = InvoiceTitle::ofUser(Auth::user())->count();

        if ($count > 20) {
            return ApiResponse::error('每个用户最多允许创建 20 个发票抬头');
        }

        $title = InvoiceTitle::create([
            'user_id' => Auth::id(),
            'type' => $request->safe()->type,
            'title' => $request->safe()->name,
            'tax_no' => $request->safe()->tax_no,
            'is_default' => $request->safe()->boolean('is_default') ?? false,
        ]);

        return ApiResponse::created(InvoiceTitleResource::make($title));
    }

    /**
     * 更新发票抬头
     *
     * @param  InvoiceTitleRequest  $request  发票抬头请求
     * @param  InvoiceTitle  $invoiceTitle  发票抬头
     *
     * @return JsonResponse 更新后的发票抬头
     */
    public function update(InvoiceTitleRequest $request, InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        $invoiceTitle->update([
            'type' => $request->safe()->type,
            'title' => $request->safe()->name,
            'tax_no' => $request->safe()->tax_no,
        ]);

        return ApiResponse::success(InvoiceTitleResource::make($invoiceTitle));
    }

    /**
     * 删除发票抬头
     *
     * @param  InvoiceTitle  $invoiceTitle  发票抬头
     *
     * @return JsonResponse 删除结果
     */
    public function destroy(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        if ($invoiceTitle->delete()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('发票抬头删除失败');
    }

    /**
     * 设置默认发票抬头
     *
     * @param  InvoiceTitle  $invoiceTitle  发票抬头
     *
     * @return JsonResponse 设置结果
     */
    public function setDefault(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        if ($invoiceTitle->setDefault()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('默认发票抬头设置失败');
    }
}
