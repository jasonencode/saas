<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceTitleRequest;
use App\Http\Resources\Users\InvoiceTitleResource;
use App\Http\Responses\ApiResponse;
use App\Models\InvoiceTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceTitleController extends Controller
{
    public function index(): JsonResponse
    {
        $titles = InvoiceTitle::ofCurrentUser()
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return ApiResponse::success(InvoiceTitleResource::collection($titles));
    }

    public function show(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        return ApiResponse::success(InvoiceTitleResource::make($invoiceTitle));
    }

    public function store(InvoiceTitleRequest $request): JsonResponse
    {
        $count = InvoiceTitle::ofUser(Auth::user())->count();

        if ($count > 20) {
            return ApiResponse::error('每个用户最多允许创建 20 个发票抬头');
        }

        $title = InvoiceTitle::create([
            'user_id' => Auth::id(),
            'type' => $request->safe()->type,
            'name' => $request->safe()->name,
            'tax_no' => $request->safe()->tax_no,
            'is_default' => $request->safe()->boolean('is_default') ?? false,
        ]);

        return ApiResponse::created(InvoiceTitleResource::make($title));
    }

    public function update(InvoiceTitleRequest $request, InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        $invoiceTitle->update($request->safe()->only(['type', 'name', 'tax_no']));

        return ApiResponse::success(InvoiceTitleResource::make($invoiceTitle));
    }

    public function destroy(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        if ($invoiceTitle->delete()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('发票抬头删除失败');
    }

    public function setDefault(InvoiceTitle $invoiceTitle): JsonResponse
    {
        $this->checkPermission($invoiceTitle);

        if ($invoiceTitle->setDefault()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('默认发票抬头设置失败');
    }
}
