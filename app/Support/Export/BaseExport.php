<?php

namespace App\Support\Export;

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
     * 默认导出格式
     */
    protected ExportFormat $defaultFormat = ExportFormat::Xlsx;

    /**
     * 创建导出实例
     *
     * @param  Builder  $builder  查询构建器
     */
    public function __construct(protected Builder $builder)
    {
        $this->fileName = $this->normalizeFileName($this->getFileName());
    }

    /**
     * 规范化文件名（确保包含合规扩展名）
     *
     * @param  string  $fileName  文件名
     *
     * @throws InvalidArgumentException 扩展名不合规
     *
     * @return string 规范化后的文件名
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
     * 校验扩展名是否合规
     *
     * @param  string  $extension  扩展名
     *
     * @throws InvalidArgumentException 扩展名不合规
     *
     * @return string 规范化后的扩展名（小写）
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

    /**
     * 获取允许的导出格式白名单
     *
     * @return array<int, ExportFormat> 允许的格式列表
     */
    protected function allowedFormats(): array
    {
        return [
            ExportFormat::Xlsx,
            ExportFormat::Csv,
        ];
    }

    /**
     * 获取默认导出格式
     *
     * @return ExportFormat 默认格式
     */
    protected function defaultFormat(): ExportFormat
    {
        return $this->defaultFormat;
    }

    /**
     * 获取导出文件名
     *
     * @return string 文件名（推荐不带扩展名，由 BaseExport 自动补全）
     */
    abstract public function getFileName(): string;

    /**
     * 获取查询构建器
     *
     * @return Builder 查询构建器
     */
    public function query(): Builder
    {
        return $this->builder;
    }
}
