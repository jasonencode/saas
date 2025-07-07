<?php

namespace App\Models\Traits;

use App\Factories\Sigma;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

trait AutoCreateOrderNo
{
    /**
     * 根据订单号查找记录
     *
     * @param  string  $no  订单号
     */
    public static function findByOrderNo(string $no): ?static
    {
        return static::query()
            ->where(static::getOrderNoField(new static), $no)
            ->first();
    }

    /**
     * 获取订单号字段名
     */
    protected static function getOrderNoField(Model $model): string
    {
        if (property_exists($model, 'orderNoField')) {
            return $model->orderNoField;
        }

        return 'no';
    }

    /**
     * 检查订单号是否存在
     */
    public static function orderNoExists(string $no): bool
    {
        return static::query()
            ->where(static::getOrderNoField(new static), $no)
            ->exists();
    }

    protected static function bootAutoCreateOrderNo(): void
    {
        static::creating(static function(Model $model) {
            $orderNo = static::generateOrderNo($model);

            while (static::where(static::getOrderNoField($model), $orderNo)->exists()) {
                $orderNo = static::generateOrderNo($model);
            }

            $model->{static::getOrderNoField($model)} = $orderNo;
        });
    }

    /**
     * 生成订单号
     *
     * @throws RuntimeException
     */
    protected static function generateOrderNo(Model $model): string
    {
        try {
            $time = explode(' ', microtime());

            $no = date('ymdHis').sprintf('%05d', $time[0] * 1e5);

            return static::getOrderNoPrefix($model).Sigma::orderNo($no);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "生成订单号失败：{$e->getMessage()}"
            );
        }
    }

    /**
     * 获取订单号前缀
     */
    protected static function getOrderNoPrefix(Model $model): string
    {
        if (property_exists($model, 'orderNoPrefix')) {
            return $model->orderNoPrefix;
        }

        return '';
    }

    /**
     * 生成新的订单号
     *
     * @throws RuntimeException
     */
    public function refreshOrderNo(): static
    {
        $this->{static::getOrderNoField($this)} = static::generateOrderNo($this);

        return $this;
    }
}
