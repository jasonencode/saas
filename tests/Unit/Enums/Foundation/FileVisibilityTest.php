<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\FileVisibility;
use PHPUnit\Framework\TestCase;

class FileVisibilityTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('public', FileVisibility::Public->value);
        $this->assertSame('private', FileVisibility::Private->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(FileVisibility::Public, FileVisibility::from('public'));
        $this->assertSame(FileVisibility::Private, FileVisibility::from('private'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(FileVisibility::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, FileVisibility::cases());
    }

    public function test_public_label(): void
    {
        $this->assertSame('公开', FileVisibility::Public->getLabel());
    }

    public function test_private_label(): void
    {
        $this->assertSame('私有（临时签名链接）', FileVisibility::Private->getLabel());
    }
}
