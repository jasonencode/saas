<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use App\Services\Mall\ProductService;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ProductBulkAuditAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'productBulkAudit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审核');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('info');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->modalDescription('仅待审核商品会被上架，其余状态自动跳过。');
        $this->deselectRecordsAfterCompletion();

        $this->action(fn (Collection $records) => $this->execute($records));
    }

    /**
     * 执行批量审核：逐条走领域服务，汇总成功/失败
     */
    public function execute(Collection $records): void
    {
        $service = service(ProductService::class);
        $success = 0;
        $failed = [];
        $skipped = 0;

        foreach ($records as $record) {
            if ($record->status !== ProductStatus::Pending) {
                $skipped++;

                continue;
            }

            try {
                $service->audit($record, ProductStatus::Up);
                $success++;
            } catch (Throwable $e) {
                $failed[] = sprintf('#%s %s', $record->getKey(), $e->getMessage());
            }
        }

        if ($failed !== []) {
            $this->failureNotificationTitle(sprintf('批量审核完成：成功 %d 条，失败 %d 条', $success, count($failed)));
            $this->failureNotificationBody(implode('；', array_slice($failed, 0, 5)));
            $this->failure();

            return;
        }

        $this->successNotificationTitle(sprintf('批量审核完成：成功 %d 条，跳过 %d 条', $success, $skipped));
        $this->success();
    }
}
