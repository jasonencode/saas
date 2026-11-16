<?php

namespace Tests\Feature\System;

use App\Http\Middleware\BlackIpList;
use App\Models\System\BlackList;
use App\Rules\IpOrCidr;
use App\Services\System\BlackListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IpBlackListTest extends TestCase
{
    use RefreshDatabase;

    public function test_blacklisted_ip_is_rejected_before_handler_runs(): void
    {
        BlackList::create(['ip' => '203.0.113.0/24']);

        $request = Request::create('/api/orders', 'GET', server: ['REMOTE_ADDR' => '203.0.113.9']);

        $handlerRan = false;

        try {
            app(BlackIpList::class)->handle($request, static function () use (&$handlerRan): Response {
                $handlerRan = true;

                return new Response('ok');
            });

            $this->fail('黑名单 IP 未被拦截');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertFalse($handlerRan, '黑名单请求不应执行到业务处理');
    }

    public function test_ipv6_entries_are_ignored_without_breaking_lookup(): void
    {
        // 黑名单当前只支持 IPv4；IPv6 记录（历史脏数据）不得拖垮整棵树的构建
        BlackList::create(['ip' => '2001:db8::/32']);

        $this->assertFalse(app(BlackListService::class)->inBlackList('127.0.0.1'));
    }

    public function test_exact_ip_match_is_blocked(): void
    {
        BlackList::create(['ip' => '198.51.100.7']);

        $this->assertTrue(app(BlackListService::class)->inBlackList('198.51.100.7'));
        $this->assertFalse(app(BlackListService::class)->inBlackList('198.51.100.8'));
    }

    public function test_ip_rule_only_accepts_ipv4_addresses_and_networks(): void
    {
        $this->assertTrue(Validator::make(['ip' => '192.168.1.1'], ['ip' => [new IpOrCidr]])->passes());
        $this->assertTrue(Validator::make(['ip' => '192.168.1.0/24'], ['ip' => [new IpOrCidr]])->passes());

        $this->assertTrue(Validator::make(['ip' => '2001:db8::1'], ['ip' => [new IpOrCidr]])->fails());
        $this->assertTrue(Validator::make(['ip' => '2001:db8::/32'], ['ip' => [new IpOrCidr]])->fails());
        $this->assertTrue(Validator::make(['ip' => '192.168.1.0/33'], ['ip' => [new IpOrCidr]])->fails());
    }
}
