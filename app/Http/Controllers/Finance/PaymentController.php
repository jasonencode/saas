<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * 发起支付
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gateway' => ['required', 'string', \Illuminate\Validation\Rule::enum(PaymentGateway::class)],
            'paymentable_type' => 'nullable|string',
            'paymentable_id' => 'nullable|integer',
            'remark' => 'nullable|string|max:500',
        ]);

        /** @var PaymentOrder $payment */
        $payment = PaymentOrder::create([
            'user_id' => Auth::id(),
            'tenant_id' => $request->tenant()?->getKey(),
            'amount' => $validated['amount'],
            'gateway' => $validated['gateway'],
            'extra' => array_filter([
                'remark' => $validated['remark'] ?? null,
            ]),
            'paymentable_type' => $validated['paymentable_type'] ?? null,
            'paymentable_id' => $validated['paymentable_id'] ?? null,
            'expired_at' => now()->addMinutes(30),
        ]);

        return ApiResponse::created(PaymentOrderResource::make($payment));
    }

    /**
     * 查询支付状态
     */
    public function show(PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        return ApiResponse::success(PaymentOrderResource::make($payment));
    }

    /**
     * 申请退款
     */
    public function refund(Request $request, PaymentOrder $payment): JsonResponse
    {
        $this->checkPermission($payment);

        if ($payment->status !== \App\Enums\Finance\PaymentStatus::Paid) {
            return ApiResponse::error('该订单未支付，无法申请退款');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string|max:1000',
        ]);

        $refund = $payment->refunds()->create([
            'tenant_id' => $payment->tenant_id,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => \App\Enums\Finance\PaymentRefundStatus::Pending,
            'created_by_type' => Auth::user()?->getMorphClass(),
            'created_by_id' => Auth::id(),
        ]);

        return ApiResponse::created([
            'refund_id' => $refund->id,
            'amount' => $refund->amount,
            'status' => $refund->status->value,
            'status_label' => $refund->status->getLabel(),
        ]);
    }
}
