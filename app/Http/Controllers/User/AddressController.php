<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\Users\AddressResource;
use App\Http\Resources\Users\RegionResource;
use App\Http\Resources\Users\RegionTwoResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\Region;
use App\Models\User\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = Address::ofUser(Auth::user())
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    public function show(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        return ApiResponse::success(AddressResource::make($address));
    }

    public function regions(Request $request): JsonResponse
    {
        $parentId = $request->parent_id ?? 0;
        $layer = $request->layer ?? 1;

        $regions = Region::where('parent_id', $parentId)->get();

        if ((int) $layer === 2) {
            return ApiResponse::success(RegionTwoResource::collection($regions));
        }

        return ApiResponse::success(RegionResource::collection($regions));
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $count = Address::ofUser(Auth::user())->count();

        if ($count > 20) {
            return ApiResponse::error('每个用户最多允许创建 20 个地址', 'ADDRESS_LIMIT_EXCEEDED');
        }

        $address = Address::create([
            'user_id' => Auth::id(),
            'name' => $request->safe()->str('name'),
            'mobile' => $request->safe()->str('mobile'),
            'province_id' => $request->safe()->integer('province_id'),
            'city_id' => $request->safe()->integer('city_id'),
            'district_id' => $request->safe()->integer('district_id'),
            'address' => $request->safe()->str('address'),
            'is_default' => $request->safe()->boolean('is_default') ?? false,
        ]);

        return ApiResponse::created(AddressResource::make($address));
    }

    public function update(AddressRequest $request, Address $address): JsonResponse
    {
        $this->checkPermission($address);

        $address->update($request->safe()->all());

        return ApiResponse::success(AddressResource::make($address));
    }

    public function destroy(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        if ($address->delete()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('地址删除失败');
    }

    public function setDefault(Address $address): JsonResponse
    {
        $this->checkPermission($address);

        if ($address->setDefault()) {
            return ApiResponse::noContent();
        }

        return ApiResponse::error('默认地址设置失败');
    }
}
