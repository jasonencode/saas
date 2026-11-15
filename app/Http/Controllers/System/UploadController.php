<?php

namespace App\Http\Controllers\System;

use App\Enums\Foundation\FileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadRequest;
use App\Http\Requests\UploadsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Foundation\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UploadController extends Controller
{
    protected string $path;

    public function __construct(protected UploadService $service) {}

    /**
     * 上传单张图片
     *
     * @param  UploadRequest  $request  上传请求
     *
     * @return JsonResponse 上传的文件信息
     */
    public function image(UploadRequest $request): JsonResponse
    {
        $file = $request->safe()->offsetGet('file');
        // 可见性由服务端决定：开放上传入口只落公开磁盘，私有用途在服务代码中显式传 Private
        $info = $this->service->save($file, FileVisibility::Public, Auth::user());

        return ApiResponse::success($info);
    }

    /**
     * 上传多张图片
     *
     * @param  UploadsRequest  $request  上传请求
     *
     * @return JsonResponse 上传的文件信息列表
     */
    public function images(UploadsRequest $request): JsonResponse
    {
        $files = $request->safe()->offsetGet('files');

        $asSave = [];
        foreach ($files as $file) {
            $asSave[] = $this->service->save($file, FileVisibility::Public, Auth::user());
        }

        return ApiResponse::success($asSave);
    }
}
