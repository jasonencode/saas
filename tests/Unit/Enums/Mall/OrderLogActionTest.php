<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\OrderLogAction;
use PHPUnit\Framework\TestCase;

class OrderLogActionTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('created', OrderLogAction::Created->value);
        $this->assertSame('canceled', OrderLogAction::Canceled->value);
        $this->assertSame('paid', OrderLogAction::Paid->value);
        $this->assertSame('preparing', OrderLogAction::Preparing->value);
        $this->assertSame('delivered', OrderLogAction::Delivered->value);
        $this->assertSame('express_deleted', OrderLogAction::ExpressDeleted->value);
        $this->assertSame('signed', OrderLogAction::Signed->value);
        $this->assertSame('completed', OrderLogAction::Completed->value);
        $this->assertSame('deleted', OrderLogAction::Deleted->value);
        $this->assertSame('address_modified', OrderLogAction::AddressModified->value);
        $this->assertSame('seller_remark_added', OrderLogAction::SellerRemarkAdded->value);
        $this->assertSame('refund_created', OrderLogAction::RefundCreated->value);
        $this->assertSame('verified', OrderLogAction::Verified->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(OrderLogAction::Created, OrderLogAction::from('created'));
        $this->assertSame(OrderLogAction::Canceled, OrderLogAction::from('canceled'));
        $this->assertSame(OrderLogAction::Paid, OrderLogAction::from('paid'));
        $this->assertSame(OrderLogAction::Preparing, OrderLogAction::from('preparing'));
        $this->assertSame(OrderLogAction::Delivered, OrderLogAction::from('delivered'));
        $this->assertSame(OrderLogAction::ExpressDeleted, OrderLogAction::from('express_deleted'));
        $this->assertSame(OrderLogAction::Signed, OrderLogAction::from('signed'));
        $this->assertSame(OrderLogAction::Completed, OrderLogAction::from('completed'));
        $this->assertSame(OrderLogAction::Deleted, OrderLogAction::from('deleted'));
        $this->assertSame(OrderLogAction::AddressModified, OrderLogAction::from('address_modified'));
        $this->assertSame(OrderLogAction::SellerRemarkAdded, OrderLogAction::from('seller_remark_added'));
        $this->assertSame(OrderLogAction::RefundCreated, OrderLogAction::from('refund_created'));
        $this->assertSame(OrderLogAction::Verified, OrderLogAction::from('verified'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(OrderLogAction::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(13, OrderLogAction::cases());
    }

    public function test_created_label_and_color(): void
    {
        $this->assertSame('订单创建', OrderLogAction::Created->getLabel());
        $this->assertSame('gray', OrderLogAction::Created->getColor());
    }

    public function test_canceled_label_and_color(): void
    {
        $this->assertSame('订单取消', OrderLogAction::Canceled->getLabel());
        $this->assertSame('red', OrderLogAction::Canceled->getColor());
    }

    public function test_paid_label_and_color(): void
    {
        $this->assertSame('订单支付', OrderLogAction::Paid->getLabel());
        $this->assertSame('blue', OrderLogAction::Paid->getColor());
    }

    public function test_preparing_label_and_color(): void
    {
        $this->assertSame('开始备货', OrderLogAction::Preparing->getLabel());
        $this->assertSame('sky', OrderLogAction::Preparing->getColor());
    }

    public function test_delivered_label_and_color(): void
    {
        $this->assertSame('订单发货', OrderLogAction::Delivered->getLabel());
        $this->assertSame('indigo', OrderLogAction::Delivered->getColor());
    }

    public function test_express_deleted_label_and_color(): void
    {
        $this->assertSame('删除发货', OrderLogAction::ExpressDeleted->getLabel());
        $this->assertSame('orange', OrderLogAction::ExpressDeleted->getColor());
    }

    public function test_signed_label_and_color(): void
    {
        $this->assertSame('订单签收', OrderLogAction::Signed->getLabel());
        $this->assertSame('teal', OrderLogAction::Signed->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('订单完成', OrderLogAction::Completed->getLabel());
        $this->assertSame('emerald', OrderLogAction::Completed->getColor());
    }

    public function test_deleted_label_and_color(): void
    {
        $this->assertSame('订单删除', OrderLogAction::Deleted->getLabel());
        $this->assertSame('red', OrderLogAction::Deleted->getColor());
    }

    public function test_address_modified_label_and_color(): void
    {
        $this->assertSame('修改地址', OrderLogAction::AddressModified->getLabel());
        $this->assertSame('amber', OrderLogAction::AddressModified->getColor());
    }

    public function test_seller_remark_added_label_and_color(): void
    {
        $this->assertSame('商家备注', OrderLogAction::SellerRemarkAdded->getLabel());
        $this->assertSame('amber', OrderLogAction::SellerRemarkAdded->getColor());
    }

    public function test_refund_created_label_and_color(): void
    {
        $this->assertSame('退款申请', OrderLogAction::RefundCreated->getLabel());
        $this->assertSame('warning', OrderLogAction::RefundCreated->getColor());
    }

    public function test_verified_label_and_color(): void
    {
        $this->assertSame('自提核销', OrderLogAction::Verified->getLabel());
        $this->assertSame('teal', OrderLogAction::Verified->getColor());
    }
}
