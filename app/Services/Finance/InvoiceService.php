<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\InvoiceStatus;
use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Events\Finance\InvoiceIssued;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceApplication;
use App\Models\Finance\InvoiceTitle;
use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService implements ServiceInterface
{
    /**
     * 创建发票申请
     *
     * 校验项：
     * - 订单租户必须与申请租户一致
     * - 订单必须属于当前申请用户
     * - 订单未关联其他待处理/已批准的发票申请（避免重复开票）
     *
     * 金额核算：
     * - 当传入 order_ids 时，开票金额由关联订单的 total_amount 累加得出
     * - 当未传入 order_ids 时，使用 data 中传入的 amount
     *
     * 抬头快照：
     * - 申请提交时从 InvoiceTitle 读取抬头信息并冻结为 title_snapshot
     * - 后续抬头被修改/删除不影响申请记录的历史准确性
     *
     * @param  int  $userId  申请用户 ID
     * @param  int  $tenantId  租户 ID
     * @param  array  $data  申请数据（invoice_title_id, amount, reason, remark, order_ids）
     *
     * @throws RuntimeException|\Throwable 当校验失败时
     *
     * @return InvoiceApplication 已创建的发票申请
     */
    public function createApplication(int $userId, int $tenantId, array $data): InvoiceApplication
    {
        $orderIds = $data['order_ids'] ?? [];

        if (!empty($orderIds)) {
            $orders = $this->validateOrders($orderIds, $tenantId, $userId);
            $data['amount'] = $this->calculateAmountFromOrders($orders);
        }

        $invoiceTitle = InvoiceTitle::where('user_id', $userId)
            ->whereKey($data['invoice_title_id'])
            ->firstOrFail();

        $application = InvoiceApplication::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'invoice_title_id' => $invoiceTitle->id,
            'title_snapshot' => $this->buildTitleSnapshot($invoiceTitle),
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'remark' => $data['remark'] ?? null,
            'status' => InvoiceApplicationStatus::Pending,
        ]);

        if (!empty($orderIds)) {
            $application->orders()->sync($orderIds);
        }

        event(new InvoiceApplicationSubmitted($application));

        return $application;
    }

    /**
     * 构建发票抬头快照
     *
     * 提取抬头的核心开票信息，冻结为 JSON 快照，
     * 确保抬头后续变更/删除不影响已生成的申请和发票记录。
     *
     * @param  InvoiceTitle  $invoiceTitle  发票抬头
     *
     * @return array<string, mixed> 抬头快照数据
     */
    private function buildTitleSnapshot(InvoiceTitle $invoiceTitle): array
    {
        return [
            'id' => $invoiceTitle->id,
            'type' => $invoiceTitle->type->value,
            'title' => $invoiceTitle->title,
            'tax_no' => $invoiceTitle->tax_no,
            'company_address' => $invoiceTitle->company_address,
            'company_phone' => $invoiceTitle->company_phone,
            'bank_name' => $invoiceTitle->bank_name,
            'bank_account' => $invoiceTitle->bank_account,
            'email' => $invoiceTitle->email,
        ];
    }

    /**
     * 校验订单是否可申请开票
     *
     * 校验项：
     * - 订单必须存在（防御性查询，Request 层已做 exists 校验）
     * - 订单租户必须与申请租户一致
     * - 订单必须属于当前申请用户
     * - 订单未关联其他待处理/已批准的发票申请（避免重复开票）
     *
     * @param  array<int>  $orderIds  订单 ID 列表
     * @param  int  $tenantId  租户 ID
     * @param  int  $userId  申请用户 ID
     *
     * @throws RuntimeException 当校验失败时
     *
     * @return Collection<int, Order> 校验通过的订单集合
     */
    private function validateOrders(array $orderIds, int $tenantId, int $userId): Collection
    {
        $orders = Order::whereIn('id', $orderIds)->get();

        if ($orders->count() !== count($orderIds)) {
            throw new RuntimeException('部分订单不存在');
        }

        foreach ($orders as $order) {
            if ((int) $order->tenant_id !== $tenantId) {
                throw new RuntimeException('订单与申请租户不一致');
            }

            if ((int) $order->user_id !== $userId) {
                throw new RuntimeException('只能为自己的订单申请开票');
            }
        }

        $alreadyInvoiced = InvoiceApplication::whereHas('orders', static function ($query) use ($orderIds): void {
            $query->whereIn('orders.id', $orderIds);
        })
            ->whereIn('status', [
                InvoiceApplicationStatus::Pending,
                InvoiceApplicationStatus::Approved,
            ])
            ->exists();

        if ($alreadyInvoiced) {
            throw new RuntimeException('订单已申请过开票');
        }

        return $orders;
    }

    /**
     * 根据关联订单核算开票金额
     *
     * 开票金额 = Σ(订单商品金额 + 运费)
     * 运费属于价外费用，按税务实践需一并开票。
     *
     * @param  Collection<int, Order>  $orders  关联订单集合
     *
     * @return string 开票金额（保留两位小数）
     */
    private function calculateAmountFromOrders(Collection $orders): string
    {
        $total = '0.00';

        foreach ($orders as $order) {
            $total = bcadd($total, (string) $order->getTotalAmount(), 2);
        }

        return $total;
    }

    /**
     * 开具发票
     *
     * @param  InvoiceApplication  $application  发票申请
     * @param  array  $data  开票数据（invoice_no, invoice_date, type, creator, recipient_email, recipient_phone）
     *
     * @throws RuntimeException|\Throwable 当申请状态非待处理时
     *
     * @return Invoice 已创建的发票
     */
    public function issue(InvoiceApplication $application, array $data): Invoice
    {
        if ($application->status !== InvoiceApplicationStatus::Pending) {
            throw new RuntimeException('只能开具待处理的发票申请');
        }

        return DB::transaction(static function () use ($application, $data): Invoice {
            $application->update([
                'status' => InvoiceApplicationStatus::Approved,
            ]);

            $invoice = Invoice::create([
                'user_id' => $application->user_id,
                'invoice_application_id' => $application->id,
                'invoice_title_id' => $application->invoice_title_id,
                'title_snapshot' => $application->title_snapshot,
                'invoice_no' => $data['invoice_no'],
                'invoice_date' => $data['invoice_date'],
                'type' => $data['type'],
                'amount' => $application->amount,
                'status' => InvoiceStatus::Issued,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'creator' => $data['creator'],
            ]);

            event(new InvoiceIssued($invoice));

            return $invoice;
        });
    }

    /**
     * 拒绝开票申请
     *
     * @param  InvoiceApplication  $application  发票申请
     * @param  string  $remark  拒绝原因
     *
     * @throws RuntimeException|\Throwable 当申请状态非待处理时
     */
    public function reject(InvoiceApplication $application, string $remark): void
    {
        if ($application->status !== InvoiceApplicationStatus::Pending) {
            throw new RuntimeException('只能拒绝待处理的发票申请');
        }

        $application->update([
            'status' => InvoiceApplicationStatus::Rejected,
            'remark' => $remark,
        ]);
    }
}
