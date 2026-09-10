<?php

namespace App\Enums\Foundation;

use Filament\Support\Contracts\HasLabel;

enum FileVisibility: string implements HasLabel
{
    case Public = 'public';

    case Private = 'private';

    public function getLabel(): string
    {
        return match ($this) {
            self::Public => '公开',
            self::Private => '私有（临时签名链接）',
        };
    }

    /**
     * 获取对应的存储磁盘名
     *
     * 公开文件写入默认磁盘，私有文件写入私有磁盘（filesystems.private）。
     *
     * @return string 磁盘名
     */
    public function disk(): string
    {
        return match ($this) {
            self::Public => config('filesystems.default'),
            self::Private => config('filesystems.private'),
        };
    }
}
