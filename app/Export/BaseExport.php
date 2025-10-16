<?php

namespace App\Export;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

abstract class BaseExport implements FromQuery, WithHeadings, WithMapping, Responsable
{
    use Exportable;

    public string $fileName = 'export.xlsx';

    public function __construct(protected Builder $builder)
    {
    }

    public function query(): Builder
    {
        return $this->builder;
    }
}
