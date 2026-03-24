<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 封面关联模型特征
 */
trait HasCovers
{
    /**
     * 初始化HasCovers特征
     *
     * @return void
     */
    public function initializeHasCovers(): void
    {
        $this->mergeCasts([
            $this->getPicturesField() => 'array',
            $this->getCoverField() => 'string',
            $this->getAvatarField() => 'string',
        ]);
    }

    /**
     * 获取图片字段名
     *
     * @return string
     */
    protected function getPicturesField(): string
    {
        return $this->picturesField ?? 'pictures';
    }

    /**
     * 获取封面字段名
     *
     * @return string
     */
    protected function getCoverField(): string
    {
        return $this->coverField ?? 'cover';
    }

    /**
     * 获取头像字段名
     *
     * @return string
     */
    protected function getAvatarField(): string
    {
        return $this->avatarField ?? 'avatar';
    }

    /**
     * 头像URL访问器
     *
     * @return Attribute
     */
    public function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->parseImageUrl($this->getAttribute($this->getAvatarField()))
        )->shouldCache();
    }

    /**
     * 解析图片URL
     *
     * @param  string|null  $image
     * @return string
     */
    protected function parseImageUrl(?string $image): string
    {
        if (empty($image)) {
            return Storage::url($this->getDefaultImage());
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        return Storage::url($image);
    }

    /**
     * 获取默认图片
     *
     * @return string
     */
    protected function getDefaultImage(): string
    {
        return $this->defaultImage ?? '';
    }

    /**
     * 封面URL访问器
     *
     * @return Attribute
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->parseImageUrl($this->getAttribute($this->getCoverField()))
        )->shouldCache();
    }

    /**
     * 图片URL列表访问器
     *
     * @return Attribute<array<string>, never>
     */
    protected function pictureUrls(): Attribute
    {
        return Attribute::get(function () {
            $pictures = $this->getAttribute($this->getPicturesField());

            return Collection::wrap($pictures ?? [])
                ->map(fn ($picture) => $this->parseImageUrl($picture))
                ->values()
                ->all();
        })->shouldCache();
    }
}
