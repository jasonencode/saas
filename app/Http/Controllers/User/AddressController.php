<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\User\AddressRequest;
use App\Http\Requests\User\RegionRequest;
use App\Http\Resources\User\AddressResource;
use App\Http\Resources\User\RegionResource;
use App\Http\Resources\User\RegionTwoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Region;
use App\Models\User\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddressController
{
    use AuthorizesModelAccess;

    /**
     * 获取地址列表
     *
     * @return JsonResponse 地址列表
     */
    public function index(): JsonResponse
    {
        $addresses = Address::ofUser(Auth::user())
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    /**
     * 获取地址详情
     *
     * @param  Address  $address  地址
     *
     * @return JsonResponse 地址详情
     */
    public function show(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        return ApiResponse::success(AddressResource::make($address));
    }

    /**
     * 获取地区列表
     *
     * @param  RegionRequest  $request  请求
     *
     * @return JsonResponse 地区列表
     */
    public function regions(RegionRequest $request): JsonResponse
    {
        $parentId = $request->safe()->integer('parent_id', 0);
        $layer = $request->safe()->integer('layer', 1);

        $regions = Region::where('parent_id', $parentId)->bySort()->get();

        if ($layer === 2) {
            return ApiResponse::success(RegionTwoResource::collection($regions));
        }

        return ApiResponse::success(RegionResource::collection($regions));
    }

    /**
     * 创建地址
     *
     * @param  AddressRequest  $request  地址请求
     *
     * @return JsonResponse 创建的地址
     */
    public function store(AddressRequest $request): JsonResponse
    {
        $count = Address::ofUser(Auth::user())->count();

        if ($count > 20) {
            return ApiResponse::error('每个用户最多允许创建 20 个地址', 'ADDRESS_LIMIT_EXCEEDED');
        }

        $address = Address::create([
            'user_id' => Auth::id(),
            'name' => $request->safe()->string('name'),
            'mobile' => $request->safe()->string('mobile'),
            'province_id' => $request->safe()->integer('province_id'),
            'city_id' => $request->safe()->integer('city_id'),
            'district_id' => $request->safe()->integer('district_id'),
            'address' => $request->safe()->string('address'),
            'is_default' => $request->safe()->boolean('is_default') ?? false,
        ]);

        return ApiResponse::created(AddressResource::make($address));
    }

    /**
     * 更新地址
     *
     * @param  AddressRequest  $request  地址请求
     * @param  Address  $address  地址
     *
     * @return JsonResponse 更新后的地址
     */
    public function update(AddressRequest $request, Address $address): JsonResponse
    {
        $this->checkPermission($address);

        $address->update($request->safe()->all());

        return ApiResponse::success(AddressResource::make($address));
    }

    /**
     * 删除地址
     *
     * @param  Address  $address  地址
     *
     * @return JsonResponse 删除结果
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        if ($address->delete()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('地址删除失败');
    }

    /**
     * 设置默认地址
     *
     * @param  Address  $address  地址
     *
     * @return JsonResponse 设置结果
     */
    public function setDefault(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        if ($address->setDefault()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('默认地址设置失败');
    }
}
