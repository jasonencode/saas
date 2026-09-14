<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\HttpMethod;
use PHPUnit\Framework\TestCase;

class HttpMethodTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('GET', HttpMethod::GET->value);
        $this->assertSame('POST', HttpMethod::POST->value);
        $this->assertSame('PUT', HttpMethod::PUT->value);
        $this->assertSame('PATCH', HttpMethod::PATCH->value);
        $this->assertSame('DELETE', HttpMethod::DELETE->value);
        $this->assertSame('OPTIONS', HttpMethod::OPTIONS->value);
        $this->assertSame('HEAD', HttpMethod::HEAD->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(HttpMethod::GET, HttpMethod::from('GET'));
        $this->assertSame(HttpMethod::POST, HttpMethod::from('POST'));
        $this->assertSame(HttpMethod::PUT, HttpMethod::from('PUT'));
        $this->assertSame(HttpMethod::PATCH, HttpMethod::from('PATCH'));
        $this->assertSame(HttpMethod::DELETE, HttpMethod::from('DELETE'));
        $this->assertSame(HttpMethod::OPTIONS, HttpMethod::from('OPTIONS'));
        $this->assertSame(HttpMethod::HEAD, HttpMethod::from('HEAD'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(HttpMethod::tryFrom('INVALID'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(7, HttpMethod::cases());
    }

    public function test_get_label_and_color(): void
    {
        $this->assertSame('GET', HttpMethod::GET->getLabel());
        $this->assertSame('green', HttpMethod::GET->getColor());
    }

    public function test_post_label_and_color(): void
    {
        $this->assertSame('POST', HttpMethod::POST->getLabel());
        $this->assertSame('blue', HttpMethod::POST->getColor());
    }

    public function test_put_label_and_color(): void
    {
        $this->assertSame('PUT', HttpMethod::PUT->getLabel());
        $this->assertSame('amber', HttpMethod::PUT->getColor());
    }

    public function test_patch_label_and_color(): void
    {
        $this->assertSame('PATCH', HttpMethod::PATCH->getLabel());
        $this->assertSame('amber', HttpMethod::PATCH->getColor());
    }

    public function test_delete_label_and_color(): void
    {
        $this->assertSame('DELETE', HttpMethod::DELETE->getLabel());
        $this->assertSame('red', HttpMethod::DELETE->getColor());
    }

    public function test_options_label_and_color(): void
    {
        $this->assertSame('OPTIONS', HttpMethod::OPTIONS->getLabel());
        $this->assertSame('neutral', HttpMethod::OPTIONS->getColor());
    }

    public function test_head_label_and_color(): void
    {
        $this->assertSame('HEAD', HttpMethod::HEAD->getLabel());
        $this->assertSame('neutral', HttpMethod::HEAD->getColor());
    }
}
