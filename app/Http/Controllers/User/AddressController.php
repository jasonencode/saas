<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\RegionResource;
use App\Http\Resources\RegionTwoResource;
use App\Models\Address;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Address::ofUser(Auth::user())
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return $this->success(AddressResource::collection($addresses));
    }

    public function show(Address $address)
    {
        $this->checkPermission($address);

        return $this->success(new AddressResource($address));
    }

    public function regions(Request $request)
    {
        $parentId = $request->parent_id ?? 0;
        $layer = $request->layer ?? 1;

        $regions = Region::where('parent_id', $parentId)->get();

        if ((int) $layer === 2) {
            return $this->success(RegionTwoResource::collection($regions));
        }

        return $this->success(RegionResource::collection($regions));
    }

    public function store(AddressRequest $request)
    {
        $count = Address::ofUser(Auth::user())->count();

        if ($count > 20) {
            return $this->error('每个用户最多允许创建20个地址');
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

        return $this->success(new AddressResource($address));
    }

    public function update(AddressRequest $request, Address $address)
    {
        $this->checkPermission($address);

        $address->update($request->safe()->all());

        return $this->success();
    }

    public function destroy(Address $address)
    {
        $this->checkPermission($address);

        if ($address->delete()) {
            return $this->success();
        } else {
            return $this->error();
        }
    }

    public function setDefault(Address $address)
    {
        $this->checkPermission($address);

        if ($address->setDefault()) {
            return $this->success();
        } else {
            return $this->error();
        }
    }
}
