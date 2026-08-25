<?php

namespace App\Http\Controllers\User;

use App\Enums\Mall\RegionLevel;
use App\Http\Controllers\Traits\AuthorizesModelAccess;
use App\Http\Requests\User\AddressRequest;
use App\Http\Requests\User\RegionRequest;
use App\Http\Resources\User\AddressResource;
use App\Http\Resources\User\RegionResource;
use App\Http\Resources\User\RegionThreeResource;
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
     * 获取默认收货地址
     *
     * @return JsonResponse 默认收货地址
     */
    public function default(): JsonResponse
    {
        $address = Address::ofUser(Auth::user())
            ->orderBy('is_default', 'desc')
            ->latest()
            ->first();

        if (!$address) {
            return ApiResponse::notFound('暂无收货地址');
        }

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
        $parentId = $request->safe()->integer('parent_id');
        $layer = $request->safe()->integer('layer', 1);

        $query = Region::where('parent_id', $parentId);

        if ($layer === 3) {
            $query->with('children.children');
        } elseif ($layer === 2) {
            $query->with('children');
        }

        $regions = $query->get();

        if ($layer === 3) {
            return ApiResponse::success(RegionThreeResource::collection($regions));
        }

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

        if ($count > Address::MAX_COUNT) {
            return ApiResponse::error('每个用户最多允许创建 '.Address::MAX_COUNT.' 个地址');
        }

        $regionIds = $this->resolveRegionIds($request);

        $address = Address::create([
            'user_id' => Auth::id(),
            'name' => $request->safe()->string('name'),
            'mobile' => $request->safe()->string('mobile'),
            'province_id' => $regionIds['province_id'],
            'city_id' => $regionIds['city_id'],
            'district_id' => $regionIds['district_id'],
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

        $regionIds = $this->resolveRegionIds($request);

        $address->update([
            'name' => $request->safe()->string('name'),
            'mobile' => $request->safe()->string('mobile'),
            'province_id' => $regionIds['province_id'],
            'city_id' => $regionIds['city_id'],
            'district_id' => $regionIds['district_id'],
            'address' => $request->safe()->string('address'),
            'is_default' => $request->safe()->boolean('is_default') ?? false,
        ]);

        return ApiResponse::success(AddressResource::make($address));
    }

    /**
     * 通过地区名称反查 ID
     *
     * @param  AddressRequest  $request  地址请求
     *
     * @return array{province_id: int|null, city_id: int|null, district_id: int|null} 地区 ID
     */
    private function resolveRegionIds(AddressRequest $request): array
    {
        $province = Region::where('name', $request->safe()->string('province'))
            ->where('level', RegionLevel::Province)
            ->first();
        $city = Region::where('name', $request->safe()->string('city'))
            ->where('level', RegionLevel::City)
            ->where('parent_id', $province->getKey())
            ->first();
        $district = Region::where('name', $request->safe()->string('district'))
            ->where('level', RegionLevel::District)
            ->where('parent_id', $city->getKey())
            ->first();

        return [
            'province_id' => $province->getKey(),
            'city_id' => $city->getKey(),
            'district_id' => $district->getKey(),
        ];
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
