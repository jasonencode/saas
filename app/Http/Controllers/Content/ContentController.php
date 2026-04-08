<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Resources\Contents\ContentCollection;
use App\Http\Resources\Contents\ContentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function index(): JsonResponse
    {
        $content = Content::ofEnabled()
            ->paginate();

        return ApiResponse::success(ContentCollection::make($content));
    }

    public function show(Content $content): JsonResponse
    {
        if ($content->isDisabled()) {
            return ApiResponse::notFound();
        }

        $content->increment('views');

        return ApiResponse::success(ContentResource::make($content));
    }
}
