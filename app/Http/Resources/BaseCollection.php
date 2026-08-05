<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

abstract class BaseCollection extends ResourceCollection
{
    protected bool $withPagination = true;

    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection,
            'page' => $this->when(
                $this->withPagination && $this->resource instanceof AbstractPaginator,
                fn () => $this->pagination()
            ),
        ];
    }

    protected function pagination(): array
    {
        return [
            'current' => $this->currentPage(),
            'total_page' => $this->lastPage(),
            'per_page' => $this->perPage(),
            'has_more' => $this->hasMorePages(),
            'total' => $this->total(),
        ];
    }

    /**
     * 禁用分页
     */
    public function withoutPagination(): static
    {
        $this->withPagination = false;

        return $this;
    }

    /**
     * 启用分页
     */
    public function withPagination(): static
    {
        $this->withPagination = true;

        return $this;
    }
}
