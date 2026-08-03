<?php

namespace App\Services\Content;

use App\Contracts\ServiceInterface;
use App\Models\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TrackVisitService implements ServiceInterface
{
    private string $keyPrefix = 'unique_visits:';

    /**
     * 记录用户访问（使用 HyperLogLog 去重）
     *
     * @param  Request  $request  请求对象（用于获取 IP）
     * @param  Model  $model  被访问的模型
     */
    public function increment(Request $request, Model $model): void
    {
        $key = $this->makeKey($model);

        Redis::pfadd($key, [$request->ip()]);
    }

    private function makeKey(Model $model): string
    {
        return $this->keyPrefix.$model->getKey();
    }

    /**
     * 获取模型的独立访客数
     *
     * @param  Model  $model  被访问的模型
     *
     * @return int 独立访客数
     */
    public function count(Model $model): int
    {
        $key = $this->makeKey($model);

        return Redis::pfcount($key);
    }
}
