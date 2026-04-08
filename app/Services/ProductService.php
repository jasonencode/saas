<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\ProductStatus;
use App\Models\Mall\Product;

class ProductService implements ServiceInterface
{
    /**
     * 审核商品
     *
     * @param  Product  $product
     * @param  ProductStatus|string  $status
     * @param  string|null  $reason
     * @return void
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
     *
     * @param  Product  $product
     * @return void
     */
    public function up(Product $product): void
    {
        $product->update(['status' => ProductStatus::Up]);
    }

    /**
     * 下架商品
     *
     * @param  Product  $product
     * @return void
     */
    public function down(Product $product): void
    {
        $product->update(['status' => ProductStatus::Down]);
    }

    /**
     * 修改浏览量
     *
     * @param  Product  $product
     * @param  int  $views
     * @return void
     */
    public function updateViews(Product $product, int $views): void
    {
        $product->views = $views;
        $product->save();
    }
}
