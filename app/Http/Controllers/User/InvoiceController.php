<?php

namespace App\Http\Controllers\User;

use App\Enums\InvoiceApplicationStatus;
use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceApplicationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Models\InvoiceApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function applications(): JsonResponse
    {
        $applications = InvoiceApplication::ofCurrentUser()
            ->latest()
            ->paginate();

        return ApiResponse::success($applications);
    }

    public function application(InvoiceApplication $application): JsonResponse
    {
        $this->checkPermission($application);

        return ApiResponse::success($application->load('invoiceTitle'));
    }

    public function apply(InvoiceApplicationRequest $request): JsonResponse
    {
        $application = InvoiceApplication::create([
            'user_id' => Auth::id(),
            'invoice_title_id' => $request->safe()->invoice_title_id,
            'amount' => $request->safe()->amount,
            'reason' => $request->safe()->reason,
            'remark' => $request->safe()->remark,
            'order_ids' => $request->safe()->order_ids,
            'status' => InvoiceApplicationStatus::Pending,
        ]);

        // 触发发票申请提交事件
        event(new InvoiceApplicationSubmitted($application));

        return ApiResponse::created($application->load('invoiceTitle'));
    }

    public function invoices(): JsonResponse
    {
        $invoices = Invoice::ofUser(Auth::user())
            ->latest()
            ->paginate();

        return ApiResponse::success($invoices);
    }

    public function invoice(Invoice $invoice): JsonResponse
    {
        $this->checkPermission($invoice);

        return ApiResponse::success($invoice->load('application.invoiceTitle'));
    }
}
