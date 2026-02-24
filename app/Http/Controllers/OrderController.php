<?php

namespace App\Http\Controllers;

use App\Dtos\Order\OrderFactory;
use App\Dtos\Order\OrderItem;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Sku;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $list = Order::ofUser(Auth::user())
            ->paginate();

        return $this->success($list);
    }

    public function create(OrderRequest $request): ?JsonResponse
    {
        $lock = Cache::lock('mall_order_'.Auth::id(), 30);

        if ($lock->get()) {
            try {
                $items = Arr::map($request->safe()->offsetGet('items'), function ($item) {
                    return OrderItem::make(Sku::find($item['sku_id']), $item['qty'], $item['remark'] ?? '');
                });

                $result = OrderFactory::make(Auth::user())
                    ->setItems($items)
                    ->setAddress($request->safe()->integer('address_id'))
                    ->create();

                return $this->success($result->toArray());
            } catch (Exception $e) {
                return $this->error($e->getMessage());
            } finally {
                $lock->release();
            }
        } else {
            return $this->error('请勿重复提交订单');
        }
    }
}
