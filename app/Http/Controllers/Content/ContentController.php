<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function index(): JsonResponse
    {
        $content = Content::ofEnabled()
            ->paginate();

        return $this->success($content);
    }

    public function show(Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return $this->error('', 404);
        }

        $content->increment('views');

        return $this->success($content);
    }
}
