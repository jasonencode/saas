<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content;

class ContentController extends Controller
{
    public function index()
    {
        $content = Content::ofEnabled()
            ->paginate();

        return $this->success($content);
    }

    public function show(Content $content)
    {
        if ($content->isDisabled()) {
            return $this->error('', 404);
        }

        $content->increment('views');

        return $this->success($content);
    }
}
