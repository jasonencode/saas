<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Dtos\Order\OrderItemDto;
use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Product;
use App\Models\Mall\ProductSku;
use App\Models\Tenant\Tenant;
use App\Services\Mall\OrderService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            $this->getSubmitFormAction()
                ->formId('form'),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = collect($data['items'] ?? [])
            ->map(function ($item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new InvalidArgumentException('商品不存在');
                }

                $sku = !empty($item['product_sku_id'])
                    ? ProductSku::find($item['product_sku_id'])
                    : null;

                return new OrderItemDto(
                    product: $product,
                    qty: (int) $item['qty'],
                    sku: $sku,
                    remark: $item['remark'] ?? null,
                );
            });

        $addressId = $data['address_id'] ?? null;
        $remark = $data['remark'] ?? null;
        $tenant = Filament::getTenant();
        if (!$tenant instanceof Tenant) {
            throw new InvalidArgumentException('当前用户不是租户');
        }

        return service(OrderService::class)->createOrder(
            tenant: $tenant,
            user: Auth::user(),
            items: $items,
            address: $addressId,
            remark: $remark,
        );
    }
}
