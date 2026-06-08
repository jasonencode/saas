<?php

namespace Tests\Feature\System;

use App\Models\System\BlackList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BlackListTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_blacklist_clears_cache(): void
    {
        Cache::put('black_ip_list', ['cached' => true], 3600);
        $this->assertTrue(Cache::has('black_ip_list'));

        BlackList::factory()->create();

        $this->assertFalse(Cache::has('black_ip_list'));
    }

    public function test_deleting_blacklist_clears_cache(): void
    {
        $blacklist = BlackList::factory()->create();
        Cache::put('black_ip_list', ['cached' => true], 3600);

        $blacklist->delete();

        $this->assertFalse(Cache::has('black_ip_list'));
    }

    public function test_updating_blacklist_clears_cache(): void
    {
        $blacklist = BlackList::factory()->create();
        Cache::put('black_ip_list', ['cached' => true], 3600);

        $blacklist->update(['remark' => 'updated']);

        $this->assertFalse(Cache::has('black_ip_list'));
    }
}
