<?php

namespace App\Http\Controllers\Mall;

use App\Dtos\Order\OrderItemDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\Mall\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Sku;
use App\Services\OrderService;
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

        return ApiResponse::paginated($list);
    }

    public function show(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(OrderResource::make($order));
    }

    public function create(OrderRequest $request): ?JsonResponse
    {
        // 创建原子锁，防止订单重复创建
        $lock = Cache::lock('mall_order_'.Auth::id(), 30);

        if ($lock->get()) {
            try {
                $items = Arr::map($request->safe()->offsetGet('items'), static function ($item) {
                    return OrderItemDto::make(Sku::find($item['sku_id']), $item['qty'], $item['remark'] ?? '');
                });

                service(OrderService::class)
                    ->createOrders(Auth::user(), $items, $request->safe()->integer('address_id'));

                return ApiResponse::created();
            } catch (Exception $e) {
                return ApiResponse::error($e->getMessage(), 'ORDER_CREATE_FAILED');
            } finally {
                $lock->release();
            }
        } else {
            return ApiResponse::error('请勿重复提交订单', 'DUPLICATE_ORDER');
        }
    }

    public function cancel(Order $order): JsonResponse
    {
        return ApiResponse::noContent('订单取消成功');
    }

    public function destroy(Order $order): JsonResponse
    {
        return ApiResponse::noContent('订单删除成功');
    }
}
