<?php

namespace Tests\Feature\System;

use App\Models\System\BlackList;
use App\Services\System\BlackListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlackListServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_ip_is_in_blacklist(): void
    {
        BlackList::factory()->create(['ip' => '192.168.1.100']);

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('192.168.1.100'));
    }

    public function test_ip_not_in_blacklist_returns_false(): void
    {
        BlackList::factory()->create(['ip' => '192.168.1.100']);

        $service = app(BlackListService::class);

        $this->assertFalse($service->inBlackList('10.0.0.1'));
    }

    public function test_empty_blacklist_returns_false(): void
    {
        $service = app(BlackListService::class);

        $this->assertFalse($service->inBlackList('192.168.1.1'));
    }

    public function test_cidr_range_ip_is_in_blacklist(): void
    {
        BlackList::factory()->cidr('10.0.0.0/24')->create();

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('10.0.0.1'));
        $this->assertTrue($service->inBlackList('10.0.0.255'));
        $this->assertFalse($service->inBlackList('10.0.1.1'));
    }

    public function test_cidr_16_range_ip_is_in_blacklist(): void
    {
        BlackList::factory()->cidr('172.16.0.0/16')->create();

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('172.16.0.1'));
        $this->assertTrue($service->inBlackList('172.16.255.255'));
        $this->assertFalse($service->inBlackList('172.17.0.1'));
    }

    public function test_cidr_32_is_treated_as_single_ip(): void
    {
        BlackList::factory()->cidr('192.168.1.1/32')->create();

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('192.168.1.1'));
        $this->assertFalse($service->inBlackList('192.168.1.2'));
    }

    public function test_cidr_0_covers_all_ips(): void
    {
        BlackList::factory()->cidr('0.0.0.0/0')->create();

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('8.8.8.8'));
        $this->assertTrue($service->inBlackList('1.1.1.1'));
    }

    public function test_multiple_ips_and_cidrs(): void
    {
        BlackList::factory()->create(['ip' => '10.10.10.10']);
        BlackList::factory()->cidr('192.168.0.0/16')->create();

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('10.10.10.10'));
        $this->assertTrue($service->inBlackList('192.168.50.1'));
        $this->assertFalse($service->inBlackList('172.16.0.1'));
    }

    public function test_invalid_ip_returns_false(): void
    {
        BlackList::factory()->create(['ip' => '192.168.1.1']);

        $service = app(BlackListService::class);

        $this->assertFalse($service->inBlackList('invalid-ip'));
        $this->assertFalse($service->inBlackList(''));
    }

    public function test_clearing_cache_reinitializes(): void
    {
        BlackList::factory()->create(['ip' => '10.0.0.1']);

        $service = app(BlackListService::class);
        $this->assertTrue($service->inBlackList('10.0.0.1'));

        // 添加新的黑名单并清除缓存
        BlackList::factory()->create(['ip' => '10.0.0.2']);
        $service->cleanCache();

        $this->assertTrue($service->inBlackList('10.0.0.2'));
    }

    public function test_single_ip_matches_exactly(): void
    {
        BlackList::factory()->create(['ip' => '192.168.1.100']);

        $service = app(BlackListService::class);

        $this->assertTrue($service->inBlackList('192.168.1.100'));
        $this->assertFalse($service->inBlackList('192.168.1.101'));
        $this->assertFalse($service->inBlackList('192.168.1.'));
    }
}
