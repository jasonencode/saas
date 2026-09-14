<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\SearchLanguage;
use PHPUnit\Framework\TestCase;

class SearchLanguageTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('simple', SearchLanguage::Simple->value);
        $this->assertSame('english', SearchLanguage::English->value);
        $this->assertSame('chinese', SearchLanguage::Chinese->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(SearchLanguage::Simple, SearchLanguage::from('simple'));
        $this->assertSame(SearchLanguage::English, SearchLanguage::from('english'));
        $this->assertSame(SearchLanguage::Chinese, SearchLanguage::from('chinese'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(SearchLanguage::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, SearchLanguage::cases());
    }

    public function test_simple_label_and_description(): void
    {
        $this->assertSame('不分词', SearchLanguage::Simple->getLabel());
        $this->assertSame('按字符分割', SearchLanguage::Simple->getDescription());
    }

    public function test_english_label_and_description(): void
    {
        $this->assertSame('英文', SearchLanguage::English->getLabel());
        $this->assertSame('英文', SearchLanguage::English->getDescription());
    }

    public function test_chinese_label_and_description(): void
    {
        $this->assertSame('中文', SearchLanguage::Chinese->getLabel());
        $this->assertSame('需配置 zhparser', SearchLanguage::Chinese->getDescription());
    }
}
