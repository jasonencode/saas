<?php

namespace App\Services\Mall\Seckill;

use App\Enums\Campaign\SeckillReserveFailure;
use App\Models\Campaign\SeckillRecord;

/**
 * 名额预扣结果
 */
final readonly class SeckillReserveResult
{
    private function __construct(
        public bool $ok,
        public ?SeckillRecord $record = null,
        public ?SeckillReserveFailure $failure = null,
    ) {}

    /**
     * 预扣成功（已生成 reserved 记录）
     */
    public static function ok(SeckillRecord $record): self
    {
        return new self(true, $record);
    }

    /**
     * 预扣失败
     */
    public static function fail(SeckillReserveFailure $failure): self
    {
        return new self(false, null, $failure);
    }

    /**
     * 失败提示，成功时为空字符串
     */
    public function message(): string
    {
        return $this->failure?->getLabel() ?? '';
    }
}
