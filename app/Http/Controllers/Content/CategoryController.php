<?php

namespace App\Http\Controllers\Content;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $list = Category::ofEnabled()
            ->where('type', CategoryType::Content)
            ->get();

        return $this->success($list);
    }

    public function show(Category $category): JsonResponse
    {

        return $this->success($category);
    }
}
