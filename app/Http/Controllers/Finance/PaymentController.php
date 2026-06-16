<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * 发起支付
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gateway' => ['required', 'string', Rule::enum(PaymentGateway::class)],
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

        if ($payment->status !== PaymentStatus::Paid) {
            return ApiResponse::error('该订单未支付，无法申请退款');
        }

        // 计算已退款金额（待审核 + 已批准 + 处理中 + 已完成）
        $refundedAmount = $payment->refunds()
            ->whereIn('status', [
                PaymentRefundStatus::Pending,
                PaymentRefundStatus::Approved,
                PaymentRefundStatus::Processing,
                PaymentRefundStatus::Completed,
            ])
            ->sum('amount');

        $refundableAmount = bcsub($payment->amount, $refundedAmount, 2);

        if (bccomp($refundableAmount, '0.01', 2) < 0) {
            return ApiResponse::error('该订单可退款金额不足');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$refundableAmount,
            'reason' => 'required|string|max:1000',
        ]);

        $refund = $payment->refunds()->create([
            'tenant_id' => $payment->tenant_id,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'status' => PaymentRefundStatus::Pending,
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
