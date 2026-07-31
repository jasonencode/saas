<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\RefundRequest;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Http\Resources\Finance\PaymentOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Finance\PaymentOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * 发起支付
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        /** @var PaymentOrder $payment */
        $payment = PaymentOrder::create([
            'user_id' => Auth::id(),
            'tenant_id' => $request->tenant()?->getKey(),
            'amount' => $request->safe()->string('amount'),
            'gateway' => $request->safe()->string('gateway'),
            'paymentable_type' => $request->safe()->string('paymentable_type'),
            'paymentable_id' => $request->safe()->integer('paymentable_id'),
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
    public function refund(RefundRequest $request, PaymentOrder $payment): JsonResponse
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

        $refund = $payment->refunds()->create([
            'tenant_id' => $payment->tenant_id,
            'amount' => $request->safe()->string('amount'),
            'reason' => $request->safe()->string('reason'),
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
