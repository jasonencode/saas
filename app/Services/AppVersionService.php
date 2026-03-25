<?php

namespace App\Services;

use App\Models\AppVersion;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class AppVersionService
{
    /**
     * 立即发布版本
     */
    public function publishNow(AppVersion $version): void
    {
        $version->publish_at = now();
        $version->save();
    }

    /**
     * 计划发布版本
     */
    public function schedulePublish(AppVersion $version, DateTimeInterface|string $publishAt): void
    {
        $version->publish_at = $publishAt instanceof DateTimeInterface
            ? Carbon::instance($publishAt)
            : Carbon::parse($publishAt);
        $version->save();
    }

    /**
     * 取消版本发布
     */
    public function unpublish(AppVersion $version): void
    {
        $version->publish_at = null;
        $version->save();
    }
}
