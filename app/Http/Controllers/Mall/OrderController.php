<?php

namespace App\Http\Controllers\Mall;

use App\Dtos\Order\OrderFactory;
use App\Dtos\Order\OrderItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\Mall\OrderCollection;
use App\Http\Resources\Mall\OrderResource;
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

        return $this->success(OrderCollection::make($list));
    }

    public function show(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return $this->error('', '404');
        }

        return $this->success(OrderResource::make($order));
    }

    public function create(OrderRequest $request): ?JsonResponse
    {
        # 创建原子锁，防止订单重复创建
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

    public function cancel(Order $order): JsonResponse
    {
        return $this->success();
    }

    public function destroy(Order $order): JsonResponse
    {
        return $this->success();
    }
}
