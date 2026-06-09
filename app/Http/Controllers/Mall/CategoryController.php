<?php

namespace App\Http\Controllers\Mall;

use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Mall\ProductCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $list = ProductCategory::ofEnabled()->get();

        return ApiResponse::success(CategoryResource::collection($list));
    }

    public function show(ProductCategory $category): JsonResponse
    {
        if ($category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
