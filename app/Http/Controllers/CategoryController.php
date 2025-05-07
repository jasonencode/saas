<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $list = Category::ofEnabled()->get();

        return $this->success($list);
    }
}
