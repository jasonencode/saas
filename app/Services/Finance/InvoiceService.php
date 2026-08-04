<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Enums\Finance\InvoiceApplicationStatus;
use App\Enums\Finance\InvoiceStatus;
use App\Events\Finance\InvoiceApplicationSubmitted;
use App\Events\Finance\InvoiceIssued;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceApplication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService implements ServiceInterface
{
    /**
     * 创建发票申请
     *
     * @param  int  $userId  申请用户 ID
     * @param  array  $data  申请数据（invoice_title_id, amount, reason, remark, order_ids）
     *
     * @throws RuntimeException|\Throwable
     *
     * @return InvoiceApplication 已创建的发票申请
     */
    public function createApplication(int $userId, array $data): InvoiceApplication
    {
        $application = InvoiceApplication::create([
            'user_id' => $userId,
            'invoice_title_id' => $data['invoice_title_id'],
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'remark' => $data['remark'] ?? null,
            'order_ids' => $data['order_ids'] ?? null,
            'status' => InvoiceApplicationStatus::Pending,
        ]);

        event(new InvoiceApplicationSubmitted($application));

        return $application;
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
