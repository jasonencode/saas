<?php

namespace App\Export;

use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

abstract class BaseExport implements FromQuery, Responsable, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * 最终用于下载响应的文件名（应包含扩展名）。
     */
    public string $fileName = '';

    /**
     * 当 getFileName() 不包含扩展名时，使用该默认扩展名补全。
     */
    protected ExportFormat $defaultFormat = ExportFormat::Xlsx;

    public function __construct(protected Builder $builder)
    {
        $this->fileName = $this->normalizeFileName($this->getFileName());
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    /**
     * 导出文件名
     *
     * - 推荐仅返回文件名主体（不带扩展名），由 BaseExport 负责补全扩展名
     * - 也允许直接返回带扩展名的完整文件名（例如：report_20260101.xlsx）
     */
    abstract public function getFileName(): string;

    /**
     * 获取默认扩展名（可通过覆盖该方法或 $defaultExtension 成员变量自定义）。
     */
    protected function defaultFormat(): ExportFormat
    {
        return $this->defaultFormat;
    }

    /**
     * 允许的扩展名白名单（小写）。
     *
     * 如需支持更多导出格式，可在子类覆盖该方法扩展白名单。
     */
    protected function allowedFormats(): array
    {
        return [
            ExportFormat::Xlsx,
            ExportFormat::Csv,
        ];
    }

    /**
     * 规范化最终文件名，保证包含“合规扩展名”。
     *
     * - 空字符串会兜底为 export
     * - 无扩展名会自动补 defaultExtension()
     * - 有扩展名会校验是否在 allowedExtensions() 内
     *
     * @throws InvalidArgumentException
     */
    protected function normalizeFileName(string $fileName): string
    {
        $fileName = trim($fileName);

        if ($fileName === '') {
            $fileName = 'export';
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($extension === '') {
            $extension = $this->validateExtension($this->defaultFormat()->value);

            return $fileName.'.'.$extension;
        }

        $this->validateExtension($extension);

        return $fileName;
    }

    /**
     * 校验扩展名是否合规，并返回规范化后的扩展名（小写）。
     *
     * @throws InvalidArgumentException
     */
    protected function validateExtension(string $extension): string
    {
        $extension = strtolower(trim($extension));

        if ($extension === '') {
            throw new InvalidArgumentException('Export file extension is required.');
        }

        if (!preg_match('/^[a-z0-9]+$/', $extension)) {
            throw new InvalidArgumentException("Invalid export file extension: $extension");
        }

        $allowedExtensions = array_map(
            static fn (ExportFormat $format): string => $format->value,
            $this->allowedFormats(),
        );

        if (!in_array($extension, $allowedExtensions, true)) {
            $allowed = implode(', ', $allowedExtensions);
            throw new InvalidArgumentException("Unsupported export file extension: $extension. Allowed: $allowed");
        }

        return $extension;
    }
}
