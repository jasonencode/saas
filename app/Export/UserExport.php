<?php

namespace App\Export;

use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class UserExport extends BaseExport
{
    protected ExportFormat $defaultFormat = ExportFormat::Csv;

    /**
     * 创建用户导出实例
     *
     * @param  Builder  $builder  查询构建器
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    /**
     * 获取导出文件名
     *
     * @return string 文件名
     */
    public function getFileName(): string
    {
        return '用户的'.date('YmdHis');
    }

    /**
     * 获取表头
     *
     * @return array<int, string> 表头列表
     */
    public function headings(): array
    {
        return [
            'ID',
            '用户名',
        ];
    }

    /**
     * 映射数据行
     *
     * @param  mixed  $row  数据行
     *
     * @return array<int, mixed> 映射后的数据
     */
    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->username,
        ];
    }

    /**
     * 转换为响应
     *
     * @param  mixed  $request  请求对象
     *
     * @return Response 响应对象
     */
    public function toResponse($request): Response
    {
        // TODO: Implement toResponse() method.
    }
}
