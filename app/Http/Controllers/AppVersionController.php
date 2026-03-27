<?php

namespace App\Http\Controllers;

use App\Http\Requests\VersionRequest;
use App\Http\Responses\ApiResponse;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AppVersionController extends Controller
{
    public function index(VersionRequest $request): JsonResponse
    {
        $platform = $request->safe()->str('platform');
        $version = $request->safe()->str('version');
        $applicationId = $request->safe()->str('application_id');

        $app = AppVersion::select([
            'version', 'description', 'version', 'force', 'download_url', 'publish_at', 'created_at',
        ])
            ->where('platform', $platform)
            ->where('application_id', $applicationId)
            ->where('publish_at', '<', Carbon::now())
//            ->orderByRaw("INET_ATON(SUBSTRING_INDEX(CONCAT(version,'.0.0.0'),'.',4)) DESC")
            ->orderByRaw("
                COALESCE(NULLIF(split_part(version, '.', 1), '')::int, 0) DESC,
                COALESCE(NULLIF(split_part(version, '.', 2), '')::int, 0) DESC,
                COALESCE(NULLIF(split_part(version, '.', 3), '')::int, 0) DESC,
                COALESCE(NULLIF(split_part(version, '.', 4), '')::int, 0) DESC
            ")
            ->first();

        $result = ['update' => false];

        if ($app) {
            $update = version_compare($version, $app->version, '<');
            if ($update) {
                $result = [
                    'update' => $update,
                    'application_id' => $applicationId,
                    'description' => $app->description,
                    'version' => $app->version,
                    'force' => $app->force,
                    'download' => $app->download_url,
                    'publish_at' => (string) ($app->publish_at ?: $app->created_at),
                ];
            }
        }

        return ApiResponse::success($result, '版本信息获取成功');
    }
}
