<?php

namespace App\Models\Traits;

use App\Factories\Sigma;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

trait AutoCreateOrderNo
{
    protected static function bootAutoCreateOrderNo(): void
    {
        static::creating(static function(Model $model) {
            do {
                $orderNo = static::generateOrderNo($model);
                $exists = static::where(static::getOrderNoField($model), $orderNo)->exists();
            } while ($exists);

            $model->{static::getOrderNoField($model)} = $orderNo;
        });
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

}
