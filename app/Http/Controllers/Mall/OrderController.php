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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class OrderController extends Controller
{
    /**
     * 获取订单列表
     */
    public function index(Request $request): JsonResponse
    {
        $list = Order::ofUser(Auth::user())
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                $builder->where('status', $request->status);
            })
            ->when($request->filled('keyword'), function (Builder $builder) use ($request) {
                $builder->where(function ($query) use ($request) {
                    $query->where('no', 'like', "%$request->keyword%")
                        ->orWhereHas('items', function ($q) use ($request) {
                            $q->whereHas('product', function ($p) use ($request) {
                                $p->where('name', 'like', "%$request->keyword%");
                            });
                        });
                });
            })
            ->latest()
            ->with(['items.product', 'address'])
            ->paginate((int) $request->input('limit', 20));

        return ApiResponse::success($list);
    }

    /**
     * 获取订单详情
     */
    public function show(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::notFound();
        }

        $order->load(['items.product', 'items.sku', 'address']);

        return ApiResponse::success(OrderResource::make($order));
    }

    /**
     * 创建订单
     */
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
            } catch (Throwable $e) {
                return ApiResponse::error($e->getMessage());
            } finally {
                $lock->release();
            }
        } else {
            return ApiResponse::error('请勿重复提交订单');
        }
    }

    /**
     * 取消订单
     */
    public function cancel(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(OrderService::class)
                ->cancel($order, Auth::user());

            return ApiResponse::noContent('订单取消成功');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * 删除订单
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->user->isNot(Auth::user())) {
            return ApiResponse::forbidden();
        }

        try {
            service(OrderService::class)
                ->delete($order, Auth::user());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::noContent('订单删除成功');
    }
}
