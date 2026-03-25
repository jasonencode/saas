<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;

class ProductService
{
    /**
     * 审核商品
     */
    public function audit(Product $product, ProductStatus|string $status, ?string $reason = null): void
    {
        $updateData = ['status' => $status];

        if ($reason) {
            $ext = $product->ext ?? [];
            $ext['audit_reason'] = $reason;
            $updateData['ext'] = $ext;
        }

        $product->update($updateData);
    }

    /**
     * 上架商品
     */
    public function up(Product $product): void
    {
        $product->update(['status' => ProductStatus::Up]);
    }

    /**
     * 下架商品
     */
    public function down(Product $product): void
    {
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
