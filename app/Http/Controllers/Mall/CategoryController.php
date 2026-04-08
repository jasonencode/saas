<?php

namespace App\Http\Controllers\Mall;

use App\Enums\Content\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $list = Category::ofEnabled()
            ->where('type', CategoryType::Product)
            ->get();

        return ApiResponse::success(CategoryResource::collection($list));
    }

    public function show(Category $category): JsonResponse
    {
        if ($category->type !== CategoryType::Product || $category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
