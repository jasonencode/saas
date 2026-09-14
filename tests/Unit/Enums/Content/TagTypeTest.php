<?php

namespace Tests\Unit\Enums\Content;

use App\Enums\Content\TagType;
use PHPUnit\Framework\TestCase;

class TagTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('content', TagType::Content->value);
        $this->assertSame('product', TagType::Product->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(TagType::Content, TagType::from('content'));
        $this->assertSame(TagType::Product, TagType::from('product'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(TagType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, TagType::cases());
    }

    public function test_content_label_and_color(): void
    {
        $this->assertSame('内容标签', TagType::Content->getLabel());
        $this->assertSame('primary', TagType::Content->getColor());
    }

    public function test_product_label_and_color(): void
    {
        $this->assertSame('商品标签', TagType::Product->getLabel());
        $this->assertSame('danger', TagType::Product->getColor());
    }
}
