<?php

namespace Tests\Feature\System;

use App\Models\System\Sensitive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SensitiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_sensitive_clears_cache(): void
    {
        Cache::put('sensitive_words_tree', ['cached' => true], 3600);
        $this->assertTrue(Cache::has('sensitive_words_tree'));

        Sensitive::factory()->create();

        $this->assertFalse(Cache::has('sensitive_words_tree'));
    }

    public function test_deleting_sensitive_clears_cache(): void
    {
        $sensitive = Sensitive::factory()->create();
        Cache::put('sensitive_words_tree', ['cached' => true], 3600);

        $sensitive->delete();

        $this->assertFalse(Cache::has('sensitive_words_tree'));
    }

    public function test_updating_sensitive_clears_cache(): void
    {
        $sensitive = Sensitive::factory()->create();
        Cache::put('sensitive_words_tree', ['cached' => true], 3600);

        $sensitive->update(['keywords' => 'newkeyword']);

        $this->assertFalse(Cache::has('sensitive_words_tree'));
    }
}
