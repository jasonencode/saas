<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Http\Requests\UploadsRequest;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    protected string $path;

    public function __construct(protected UploadService $service)
    {
    }

    public function image(UploadRequest $request): JsonResponse
    {
        $file = $request->safe()->offsetGet('file');
        $info = $this->service->save($file);

        return $this->success($info);
    }

    public function images(UploadsRequest $request): JsonResponse
    {
        $files = $request->safe()->offsetGet('files');

        $asSave = [];
        foreach ($files as $file) {
            $asSave[] = $this->service->save($file);
        }

        return $this->success($asSave);
    }
}
