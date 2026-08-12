<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundType: string implements HasColor, HasLabel
{
    /**
     * 退货退款
     */
    case ReturnRefund = 'return_refund';
    /**
     * 仅退款
     */
    case OnlyRefund = 'only_refund';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReturnRefund => '退货退款',
            self::OnlyRefund => '仅退款',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReturnRefund => 'orange',
            self::OnlyRefund => 'blue',
        };
    }

    /**
     * 对应类型的退款原因选项
     *
     * @return array<string, string>
     */
    public function reasons(): array
    {
        return match ($this) {
            self::OnlyRefund => [
                RefundReason::NotWant->value => '不想要了',
                RefundReason::WrongOrder->value => '拍错/多拍',
                RefundReason::NotReceived->value => '未收到货',
                RefundReason::LateDelivery->value => '未按时发货',
                RefundReason::QualityIssue->value => '质量问题',
                RefundReason::Damaged->value => '商品破损',
                RefundReason::NotAsDescribed->value => '描述不符',
                RefundReason::MissingItem->value => '少发/漏发',
                RefundReason::Counterfeit->value => '假货',
                RefundReason::Other->value => '其他',
            ],
            self::ReturnRefund => [
                RefundReason::NotWant->value => '不想要了',
                RefundReason::QualityIssue->value => '质量问题',
                RefundReason::Damaged->value => '商品破损',
                RefundReason::NotAsDescribed->value => '描述不符',
                RefundReason::SizeIssue->value => '尺寸不合适',
                RefundReason::WrongItem->value => '发错货',
                RefundReason::MissingItem->value => '少发/漏发',
                RefundReason::Other->value => '其他',
            ],
        };
    }
}
