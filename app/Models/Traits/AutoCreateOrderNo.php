<?php

namespace App\Models\Traits;

use App\Extensions\Sigma\Sigma;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

/**
 * 自动生成订单号特征
 *
 * @property string $no
 */
trait AutoCreateOrderNo
{
    /**
     * 启动自动生成订单号特征
     *
     * @return void
     */
    protected static function bootAutoCreateOrderNo(): void
    {
        static::creating(static function (Model $model) {
            do {
                $orderNo = static::generateOrderNo($model);
                $exists = static::where(static::getOrderNoField($model), $orderNo)->exists();
            } while ($exists);

            $model->{static::getOrderNoField($model)} = $orderNo;
        });
    }

    /**
     * 生成订单号
     *
     * @param  Model  $model
     * @return string
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
     *
     * @param  Model  $model
     * @return string
     */
    protected static function getOrderNoPrefix(Model $model): string
    {
        if (property_exists($model, 'orderNoPrefix')) {
            return $model->orderNoPrefix;
        }

        return '';
    }

    /**
     * 获取订单号字段名
     *
     * @param  Model  $model
     * @return string
     */
    protected static function getOrderNoField(Model $model): string
    {
        if (property_exists($model, 'orderNoField')) {
            return $model->orderNoField;
        }

        return 'no';
    }
}
