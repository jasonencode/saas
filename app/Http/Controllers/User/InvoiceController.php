<?php

namespace App\Http\Controllers\User;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Mall\OrderStatus;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\User\InvoiceApplicationRequest;
use App\Http\Resources\User\InvoicableOrderCollection;
use App\Http\Resources\User\InvoiceApplicationCollection;
use App\Http\Resources\User\InvoiceApplicationResource;
use App\Http\Resources\User\InvoiceCollection;
use App\Http\Resources\User\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceApplication;
use App\Models\Mall\Order;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController
{
    use AuthorizesModelAccess;

    /**
     * 可开票订单列表
     *
     * 返回当前用户已支付且未被其他待处理/已批准发票申请关联的订单，分页展示。
     */
    public function invoicableOrders(): JsonResponse
    {
        $user = Auth::user();

        $excludeOrderIds = InvoiceApplication::where('user_id', $user->id)
            ->whereIn('status', [
                InvoiceApplicationStatus::Pending,
                InvoiceApplicationStatus::Approved,
            ])
            ->with('orders:id')
            ->get()
            ->flatMap(fn (InvoiceApplication $application) => $application->orders->modelKeys())
            ->unique()
            ->values();

        $orders = Order::ofUser($user)
            ->whereNotIn('status', [OrderStatus::Pending, OrderStatus::Canceled])
            ->when($excludeOrderIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $excludeOrderIds))
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(new InvoicableOrderCollection($orders));
    }

    /**
     * 获取发票申请列表
     *
     * @return JsonResponse 发票申请列表
     */
    public function applications(): JsonResponse
    {
        $applications = InvoiceApplication::ofCurrentUser()
            ->with('invoiceTitle')
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(new InvoiceApplicationCollection($applications));
    }

    /**
     * 获取发票申请详情
     *
     * @param  InvoiceApplication  $application  发票申请
     *
     * @return JsonResponse 发票申请详情
     */
    public function application(InvoiceApplication $application): JsonResponse
    {
        $this->checkPermission($application);

        return ApiResponse::success(new InvoiceApplicationResource($application->load(['invoiceTitle', 'orders'])));
    }

    /**
     * 创建发票申请
     *
     * @param  InvoiceApplicationRequest  $request  发票申请请求
     * @param  InvoiceService  $service  发票服务
     *
     * @return JsonResponse 创建的发票申请
     */
    public function apply(InvoiceApplicationRequest $request, InvoiceService $service): JsonResponse
    {
        $user = Auth::user();

        $orderIds = $request->input('order_ids', []);
        $tenantId = Order::whereIn('id', $orderIds)->value('tenant_id');

        $application = $service->createApplication(
            $user->id,
            $tenantId,
            $request->safe()->only(['invoice_title_id', 'reason', 'remark', 'order_ids']),
        );

        return ApiResponse::created(new InvoiceApplicationResource($application->load(['invoiceTitle', 'orders'])));
    }

    /**
     * 获取发票列表
     *
     * @return JsonResponse 发票列表
     */
    public function invoices(): JsonResponse
    {
        $invoices = Invoice::ofUser(Auth::user())
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(new InvoiceCollection($invoices));
    }

    /**
     * 获取发票详情
     *
     * @param  Invoice  $invoice  发票
     *
     * @return JsonResponse 发票详情
     */
    public function invoice(Invoice $invoice): JsonResponse
    {
        $this->checkPermission($invoice);

        return ApiResponse::success(new InvoiceResource($invoice->load('application.invoiceTitle')));
    }
}
