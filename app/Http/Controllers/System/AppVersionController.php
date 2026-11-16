<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\VersionRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Content\AppVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AppVersionController extends Controller
{
    /**
     * 获取应用版本更新信息
     *
     * 排序在 PHP 内完成，避免依赖 PostgreSQL 专用的 `split_part`，
     * 同时保证 SQLite 测试与生产环境口径一致。
     *
     * @param  VersionRequest  $request  版本请求
     *
     * @return JsonResponse 版本更新信息
     */
    public function index(VersionRequest $request): JsonResponse
    {
        $platform = $request->safe()->str('platform');
        $version = $request->safe()->str('version');
        $applicationId = $request->safe()->str('application_id');

        $app = AppVersion::select([
            'version', 'description', 'force', 'download_url', 'publish_at', 'created_at',
        ])
            ->where('platform', $platform)
            ->where('application_id', $applicationId)
            // publish_at 为空表示立即发布，不应被排除
            ->where(static function (Builder $query): void {
                $query->whereNull('publish_at')
                    ->orWhere('publish_at', '<', Carbon::now());
            })
            ->get()
            ->sortByDesc(static fn (AppVersion $item): string => self::versionKey($item->version))
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

    /**
     * 生成版本排序键
     *
     * 将 `1.9` / `1.10.0` 之类的版本号补零对齐，字符串降序即等价于
     * 按主次修订号数值降序，避免逐段 SQL 解析。
     *
     * @param  string  $version  版本号
     */
    private static function versionKey(string $version): string
    {
        $parts = array_slice(explode('.', $version), 0, 4);
        $parts = array_pad($parts, 4, '0');

        return implode('.', array_map(
            static fn (string $part): string => str_pad((string) (int) $part, 10, '0', STR_PAD_LEFT),
            $parts
        ));
    }
}
