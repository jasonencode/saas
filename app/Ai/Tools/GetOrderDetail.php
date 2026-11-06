<?php

namespace App\Ai\Tools;

use App\Ai\Tools\Concerns\InteractsWithOrders;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetOrderDetail implements Tool
{
    use InteractsWithOrders;

    /**
     * @param  int|null  $tenantId  租户 ID；为空时查询全平台（仅运营端使用）
     */
    public function __construct(protected ?int $tenantId = null) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '按订单号查询订单全链路详情：状态、金额、商品明细、收货地址、支付记录、物流、退款、自提核销与买卖家备注。用户问「这单什么情况 / 为什么没发货 / 退款到哪一步」时使用。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $no = trim((string) $request->str('no'));

        if ($no === '') {
            return '请提供订单号（no）。';
        }

        $order = $this->orderQuery()
            ->with([
                'items',
                'address.province',
                'address.city',
                'address.district',
                'shippings' => fn ($query) => $query->with('express')->latest('id')->limit(5),
                'refunds' => fn ($query) => $query->latest('id')->limit(5),
                'paymentOrders' => fn ($query) => $query->latest('id')->limit(5),
                'pickupPoint',
                'verifiedBy',
                'user.profile',
            ])
            ->where('no', $no)
            ->first();

        if ($order === null) {
            return "未找到订单：{$no}";
        }

        return (string) json_encode([
            'order_no' => $order->no,
            'status' => $order->status->getLabel(),
            'fulfillment' => $order->fulfillment_type->getLabel(),
            'amounts' => [
                'goods' => round((float) $order->amount, 2),
                'freight' => round((float) $order->freight, 2),
                'coupon_discount' => round((float) $order->coupon_discount, 2),
                'total' => round($order->total_amount, 2),
            ],
            'times' => [
                'created_at' => $order->created_at?->toDateTimeString(),
                'paid_at' => $order->paid_at?->toDateTimeString(),
                'expired_at' => $order->expired_at?->toDateTimeString(),
                'signed_at' => $order->signed_at?->toDateTimeString(),
                'verified_at' => $order->verified_at?->toDateTimeString(),
                'status_updated_at' => $order->updated_at?->toDateTimeString(),
                'status_hours' => $order->updated_at
                    ? round(abs($order->updated_at->diffInMinutes(now())) / 60, 1)
                    : null,
            ],
            'user' => [
                'id' => $order->user?->id,
                'username' => $order->user?->username,
                'nickname' => $order->user?->profile?->nickname,
            ],
            'address' => $order->address === null ? null : [
                'name' => $order->address->name,
                'mobile' => $this->maskMobile($order->address->mobile),
                'full_address' => $order->address->full_address,
            ],
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->orderable_name,
                'qty' => (int) $item->qty,
                'price' => round((float) $item->price, 2),
                'coupon_discount' => round((float) $item->coupon_discount, 2),
                'subtotal' => round($item->qty * (float) $item->price, 2),
            ])->values()->all(),
            'payments' => $order->paymentOrders->map(fn ($payment) => [
                'no' => $payment->no,
                'gateway' => $payment->gateway?->getLabel(),
                'status' => $payment->status?->getLabel(),
                'amount' => round((float) $payment->amount, 2),
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ])->values()->all(),
            'shippings' => $order->shippings->map(fn ($shipping) => [
                'express' => $shipping->express?->name,
                'express_no' => $shipping->express_no,
                'delivery_at' => $shipping->delivery_at?->toDateTimeString(),
                'sign_at' => $shipping->sign_at?->toDateTimeString(),
            ])->values()->all(),
            'refund_summary' => [
                'count' => $order->refunds()->count(),
                'amount' => round((float) $order->refunds()->sum('total'), 2),
            ],
            'refunds' => $order->refunds->map(fn ($refund) => [
                'no' => $refund->no,
                'type' => $refund->type?->getLabel(),
                'status' => $refund->status?->getLabel(),
                'amount' => round((float) $refund->total, 2),
                'reason' => $refund->reason?->getLabel(),
                'created_at' => $refund->created_at?->toDateTimeString(),
                'refund_at' => $refund->refund_at?->toDateTimeString(),
            ])->values()->all(),
            'pickup' => $order->fulfillment_type->value !== 'pickup' ? null : [
                'code' => $order->pickup_code,
                'point' => $order->pickupPoint?->name,
                'verified_by' => $order->verifiedBy?->name,
                'verified_at' => $order->verified_at?->toDateTimeString(),
            ],
            'remarks' => [
                'buyer' => $order->remark,
                'seller' => $order->seller_remark,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'no' => $schema->string()->description('订单号，精确匹配'),
        ];
    }
}
