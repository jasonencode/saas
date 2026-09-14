<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\AliyunDnsType;
use PHPUnit\Framework\TestCase;

class AliyunDnsTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('A', AliyunDnsType::A->value);
        $this->assertSame('AAAA', AliyunDnsType::AAAA->value);
        $this->assertSame('CNAME', AliyunDnsType::CNAME->value);
        $this->assertSame('TXT', AliyunDnsType::TXT->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(AliyunDnsType::A, AliyunDnsType::from('A'));
        $this->assertSame(AliyunDnsType::AAAA, AliyunDnsType::from('AAAA'));
        $this->assertSame(AliyunDnsType::CNAME, AliyunDnsType::from('CNAME'));
        $this->assertSame(AliyunDnsType::TXT, AliyunDnsType::from('TXT'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AliyunDnsType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, AliyunDnsType::cases());
    }

    public function test_a_label(): void
    {
        $this->assertSame('A记录', AliyunDnsType::A->getLabel());
    }

    public function test_aaaa_label(): void
    {
        $this->assertSame('AAAA记录(IPv6)', AliyunDnsType::AAAA->getLabel());
    }

    public function test_cname_label(): void
    {
        $this->assertSame('CNAME记录', AliyunDnsType::CNAME->getLabel());
    }

    public function test_txt_label(): void
    {
        $this->assertSame('TXT记录', AliyunDnsType::TXT->getLabel());
    }
}
