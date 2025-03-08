<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasCovers
{
    public function initializeHasCovers(): void
    {
        $this->mergeCasts([
            $this->getPicturesField() => 'array',
            $this->getCoverField() => 'string',
            $this->getAvatarField() => 'string',
        ]);
    }

    protected function getPicturesField(): string
    {
        return $this->picturesField ?? 'pictures';
    }

    protected function getCoverField(): string
    {
        return $this->coverField ?? 'cover';
    }

    protected function getAvatarField(): string
    {
        return $this->avatarField ?? 'avatar';
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn() => $this->parseImageUrl($this->getAttribute($this->getAvatarField()))
        )->shouldCache();
    }

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

    protected function getDefaultImage(): string
    {
        return $this->defaultImage ?? '';
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn() => $this->parseImageUrl($this->getAttribute($this->getCoverField()))
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
                ->map(fn($picture) => $this->parseImageUrl($picture))
                ->values()
                ->all();
        })->shouldCache();
    }
}
