<?php

namespace App\Http\Controllers\Mall;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $list = Category::ofEnabled()
            ->where('type', CategoryType::Product)
            ->get();

        return $this->success(CategoryResource::collection($list));
    }

    public function show(Category $category): JsonResponse
    {
        if ($category->type !== CategoryType::Product || $category->isDisabled()) {
            return $this->error('', 404);
        }

        return $this->success(CategoryResource::make($category));
    }
}
