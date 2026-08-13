<?php

namespace App\Console\Commands\Seeders;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Models\Finance\InvoiceApplication;
use App\Models\Finance\InvoiceTitle;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('seed:invoice-applications')]
class InvoiceApplicationSeeder extends Command
{
    public function handle(): void
    {
        $tenantId = (int) select(
            label: '选择租户',
            options: Tenant::ofEnabled()->pluck('name', 'id')->toArray(),
        );
        $count = (int) text(
            label: '要生成的发票申请数量',
            default: '5',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        $user = User::find(1);
        if (!$user) {
            $this->error('未找到用户');

            return;
        }

        $invoiceTitle = $user->invoiceTitles()->first();
        if (!$invoiceTitle) {
            $this->error('该用户没有发票抬头，请先创建发票抬头');

            return;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $this->createApplication($user, $invoiceTitle, $tenantId);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("发票申请生成完成，共 {$count} 笔");
    }

    private function createApplication(User $user, InvoiceTitle $invoiceTitle, int $tenantId): void
    {
        InvoiceApplication::create([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
            'invoice_title_id' => $invoiceTitle->id,
            'amount' => random_int(100, 5000) / 100,
            'status' => InvoiceApplicationStatus::Pending,
            'reason' => fake('zh_CN')->sentence(),
        ]);
    }
}
