<?php

namespace App\Export;

class UserExport extends BaseExport
{
    public string $fileName = '用户.xlsx';

    public function headings(): array
    {
        return [
            'ID', '用户名',
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
