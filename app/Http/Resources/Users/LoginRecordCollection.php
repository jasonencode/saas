<?php

namespace App\Http\Resources\Users;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;

class LoginRecordCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function ($item) {
                return [
                    'ip' => $item->ip,
                    'user_agent' => $item->user_agent,
                    'created_at' => (string) $item->created_at,
                ];
            }),
            'page' => $this->when(
                $this->withPagination && $this->resource instanceof AbstractPaginator,
                fn() => $this->pagination()
            ),
        ];
    }
}
