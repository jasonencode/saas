<?php

namespace App\Export;

use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\Builder;

class UserExport extends BaseExport
{
    protected ExportFormat $defaultFormat = ExportFormat::Csv;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    public function getFileName(): string
    {
        return '用户的'.date('YmdHis');
    }

    public function headings(): array
    {
        return [
            'ID',
            '用户名',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->username,
        ];
    }
}
