<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\User\InvoiceApplicationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceApplication;
use App\Services\Finance\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController
{
    use AuthorizesModelAccess;

    public function applications(): JsonResponse
    {
        $applications = InvoiceApplication::ofCurrentUser()
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success($applications);
    }

    public function application(InvoiceApplication $application): JsonResponse
    {
        $this->checkPermission($application);

        return ApiResponse::success($application->load('invoiceTitle'));
    }

    public function apply(InvoiceApplicationRequest $request, InvoiceService $service): JsonResponse
    {
        $application = $service->createApplication(
            Auth::id(),
            $request->safe()->only(['invoice_title_id', 'amount', 'reason', 'remark', 'order_ids']),
        );

        return ApiResponse::created($application->load('invoiceTitle'));
    }

    public function invoices(): JsonResponse
    {
        $invoices = Invoice::ofUser(Auth::user())
            ->latest()
            ->paginate(min(request()->integer('per_page', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success($invoices);
    }

    public function invoice(Invoice $invoice): JsonResponse
    {
        $this->checkPermission($invoice);

        return ApiResponse::success($invoice->load('application.invoiceTitle'));
    }
}
