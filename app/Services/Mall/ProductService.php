<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;
use RuntimeException;

class ProductService implements ServiceInterface
{
    /**
     * 审核允许的目标状态
     */
    private const array AUDITABLE_TARGETS = [
        ProductStatus::Up,
        ProductStatus::Rejected,
    ];

    /**
     * 审核商品
     *
     * @throws RuntimeException 当状态转换不允许时抛出
     */
    public function audit(Product $product, ProductStatus|string $status, ?string $reason = null): void
    {
        $targetStatus = is_string($status) ? ProductStatus::from($status) : $status;

        if (!in_array($targetStatus, self::AUDITABLE_TARGETS, true)) {
            throw new RuntimeException('审核目标状态无效: '.$targetStatus->value);
        }

        if ($product->status !== ProductStatus::Pending) {
            throw new RuntimeException('仅审核中的商品可进行审核操作');
        }

        $updateData = ['status' => $targetStatus];

        if ($reason) {
            $ext = $product->ext ?? [];
            $ext['audit_reason'] = $reason;
            $updateData['ext'] = $ext;
        }

        $product->update($updateData);
    }

    /**
     * 上架商品
     *
     * @throws RuntimeException 当状态转换不允许时抛出
     */
    public function up(Product $product): void
    {
        $allowedStatuses = [ProductStatus::Down, ProductStatus::Rejected];

        if (!in_array($product->status, $allowedStatuses, true)) {
            throw new RuntimeException('当前状态不可上架: '.$product->status->getLabel());
        }

        $product->update(['status' => ProductStatus::Up]);
    }

    /**
     * 下架商品
     *
     * @throws RuntimeException 当状态转换不允许时抛出
     */
    public function down(Product $product): void
    {
        if ($product->status !== ProductStatus::Up) {
            throw new RuntimeException('仅上架中的商品可下架');
        }

        $product->update(['status' => ProductStatus::Down]);
    }

    /**
     * 修改浏览量
     */
    public function updateViews(Product $product, int $views): void
    {
        $product->views = $views;
        $product->save();
    }
}
