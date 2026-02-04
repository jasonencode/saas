<?php

namespace App\Services;

use App\Models\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TrackVisitService
{
    private string $keyPrefix = 'unique_visits:';

    public function increment(Request $request, Model $model): void
    {
        $key = $this->makeKey($model);

        Redis::pfadd($key, [$request->ip()]);
    }

    private function makeKey(Model $model): string
    {
        return $this->keyPrefix.$model->getKey();
    }

    public function count(Model $model): int
    {
        $key = $this->makeKey($model);

        return Redis::pfcount($key);
    }
}
