<?php

namespace App\Http\Controllers\Content;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $list = Category::ofEnabled()
            ->where('type', CategoryType::Content)
            ->get();

        return ApiResponse::success(CategoryResource::collection($list));
    }

    public function show(Category $category): JsonResponse
    {
        if ($category->type !== CategoryType::Content || $category->isDisabled()) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(CategoryResource::make($category));
    }
}
