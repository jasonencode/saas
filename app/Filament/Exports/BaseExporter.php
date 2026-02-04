<?php

namespace App\Filament\Exports;

use Carbon\CarbonInterface;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

abstract class BaseExporter extends Exporter
{
    abstract public static function getName(): string;

    public function getJobRetryUntil(): ?CarbonInterface
    {
        return null;
    }

    public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
            ExportFormat::Csv,
        ];
    }

    public function getFileName(Export $export): string
    {
        return static::getName() . '-' . date('Y-m-d-H-i-s');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = sprintf('您的【%s】您的已导出成功：%s行。', static::getName(), number_format($export->successful_rows));

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= '导出失败：' . number_format($failedRowsCount) . '行';
        }

        return $body;
    }
}
