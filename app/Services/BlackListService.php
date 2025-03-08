<?php

namespace App\Services;

use App\Models\BlackList;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class BlackListService
{
    private const CACHE_TTL = 86400;

    private const IPV4_SEGMENTS = 4;

    private array $tree = [];

    private bool $isInitialized = false;

    private string $cacheKey = 'black_ip_list';

    public function cleanCache(): void
    {
        Cache::forget($this->cacheKey);
        $this->isInitialized = false;
    }

    public function inBlackList(string $ip): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $node = $this->tree;
        $parts = array_map('intval', explode('.', $ip));

        for ($i = 0; $i < self::IPV4_SEGMENTS; $i++) {
            $part = $parts[$i];

            if (isset($node['ranges'])) {
                foreach ($node['ranges'] as [$start, $end]) {
                    if ($part >= $start && $part <= $end) {
                        return true;
                    }
                }
            }

            if (!isset($node[$part])) {
                return false;
            }
            $node = $node[$part];
        }

        return isset($node['end']);
    }

    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->tree = Cache::remember($this->cacheKey, now()->addSeconds(self::CACHE_TTL), function () {
            $ips = BlackList::pluck('ip')
                ->toArray();

            return $this->buildTree($ips);
        });

        $this->isInitialized = true;
    }

    private function buildTree(array $ips): array
    {
        $tree = [];

        foreach ($ips as $ip) {
            if (str_contains($ip, '/')) {
                [$startIp, $endIp] = $this->cidrToRange($ip);
                $this->insertRange($tree, $startIp, $endIp);
            } else {
                $current = &$tree;
                $segments = explode('.', $ip);

                foreach ($segments as $segment) {
                    if (!isset($current[$segment])) {
                        $current[$segment] = [];
                    }
                    $current = &$current[$segment];
                }

                $current['end'] = true;
            }
        }

        return $tree;
    }

    private function cidrToRange(string $cidr): array
    {
        if (!str_contains($cidr, '/')) {
            throw new InvalidArgumentException('Invalid CIDR format');
        }

        [$ip, $prefix] = explode('/', $cidr);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException('Invalid IP address');
        }

        $prefix = (int) $prefix;
        if ($prefix < 0 || $prefix > 32) {
            throw new InvalidArgumentException('Invalid prefix length');
        }

        $ipInt = ip2long($ip);
        $mask = ~((1 << (32 - $prefix)) - 1);
        $startIp = long2ip($ipInt & $mask);
        $endIp = long2ip($ipInt | (~$mask));

        return [$startIp, $endIp];
    }

    private function insertRange(array &$node, string $startIp, string $endIp): void
    {
        $startParts = explode('.', $startIp);
        $endParts = explode('.', $endIp);

        for ($i = 0; $i < 4; $i++) {
            $startPart = (int) $startParts[$i];
            $endPart = (int) $endParts[$i];

            if ($startPart === $endPart) {
                if (!isset($node[$startPart])) {
                    $node[$startPart] = [];
                }
                $node = &$node[$startPart];
            } else {
                if (!isset($node['ranges'])) {
                    $node['ranges'] = [];
                }
                $node['ranges'][] = [$startPart, $endPart];
                break;
            }
        }
    }
}
