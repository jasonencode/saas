<?php

namespace Tests\Unit\Enums\Content;

use App\Enums\Content\CategoryType;
use PHPUnit\Framework\TestCase;

class CategoryTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('content', CategoryType::Content->value);
        $this->assertSame('product', CategoryType::Product->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(CategoryType::Content, CategoryType::from('content'));
        $this->assertSame(CategoryType::Product, CategoryType::from('product'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(CategoryType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, CategoryType::cases());
    }

    public function test_content_label_and_color(): void
    {
        $this->assertSame('内容分类', CategoryType::Content->getLabel());
        $this->assertSame('primary', CategoryType::Content->getColor());
    }

    public function test_product_label_and_color(): void
    {
        $this->assertSame('商品分类', CategoryType::Product->getLabel());
        $this->assertSame('danger', CategoryType::Product->getColor());
    }
}
