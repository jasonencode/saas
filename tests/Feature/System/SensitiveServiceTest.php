<?php

namespace Tests\Feature\System;

use App\Models\System\Sensitive;
use App\Services\System\SensitiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_contains_detects_sensitive_word(): void
    {
        Sensitive::factory()->create(['keywords' => '敏感词']);

        $service = app(SensitiveService::class);

        $this->assertTrue($service->contains('这是一段包含敏感词的文本'));
    }

    public function test_contains_returns_false_when_no_sensitive_word(): void
    {
        Sensitive::factory()->create(['keywords' => '敏感词']);

        $service = app(SensitiveService::class);

        $this->assertFalse($service->contains('这是一段正常文本'));
    }

    public function test_contains_with_empty_text(): void
    {
        $service = app(SensitiveService::class);

        $this->assertFalse($service->contains(''));
    }

    public function test_contains_is_case_insensitive(): void
    {
        Sensitive::factory()->create(['keywords' => 'badword']);

        $service = app(SensitiveService::class);

        $this->assertTrue($service->contains('This contains BADWORD here'));
        $this->assertTrue($service->contains('badword'));
        $this->assertTrue($service->contains('BadWord'));
    }

    public function test_find_returns_matched_sensitive_words(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);
        Sensitive::factory()->create(['keywords' => '色情']);

        $service = app(SensitiveService::class);

        $words = $service->find('包含暴力和色情的文本');

        $this->assertCount(2, $words);
        $this->assertContains('暴力', $words);
        $this->assertContains('色情', $words);
    }

    public function test_find_returns_empty_array_when_no_match(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);

        $service = app(SensitiveService::class);

        $words = $service->find('干净文本');

        $this->assertEmpty($words);
    }

    public function test_find_returns_unique_words(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);

        $service = app(SensitiveService::class);

        $words = $service->find('暴力文本，再次暴力出现');

        $this->assertCount(1, $words);
        $this->assertSame(['暴力'], $words);
    }

    public function test_filter_replaces_sensitive_words(): void
    {
        Sensitive::factory()->create(['keywords' => '敏感词']);

        $service = app(SensitiveService::class);

        $result = $service->filter('这是一段包含敏感词的文本');

        $this->assertStringContainsString('***', $result);
        $this->assertStringNotContainsString('敏感词', $result);
    }

    public function test_filter_with_custom_replace_char(): void
    {
        Sensitive::factory()->create(['keywords' => 'bad']);

        $service = new SensitiveService('#');

        $result = $service->filter('this is bad word');

        $this->assertSame('this is ### word', $result);
    }

    public function test_filter_no_sensitive_word_returns_original(): void
    {
        $service = app(SensitiveService::class);

        $text = '这是一段正常文本';
        $result = $service->filter($text);

        $this->assertSame($text, $result);
    }

    public function test_filter_empty_text(): void
    {
        $service = app(SensitiveService::class);

        $this->assertSame('', $service->filter(''));
    }

    public function test_batch_import_adds_new_words(): void
    {
        $service = app(SensitiveService::class);

        $count = $service->batchImport(['暴力', '色情', '赌博']);

        $this->assertSame(3, $count);
        $this->assertDatabaseHas('sensitives', ['keywords' => '暴力']);
        $this->assertDatabaseHas('sensitives', ['keywords' => '色情']);
        $this->assertDatabaseHas('sensitives', ['keywords' => '赌博']);
    }

    public function test_batch_import_skips_duplicates(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);
        $service = app(SensitiveService::class);

        $count = $service->batchImport(['暴力', '色情']);

        $this->assertSame(1, $count); // 只有色情是新词
        $this->assertDatabaseHas('sensitives', ['keywords' => '暴力']);
        $this->assertDatabaseHas('sensitives', ['keywords' => '色情']);
    }

    public function test_batch_import_skips_empty_values(): void
    {
        $service = app(SensitiveService::class);

        $count = $service->batchImport(['', null]);

        $this->assertSame(0, $count);
    }

    public function test_batch_import_with_all_existing_words_returns_zero(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);
        $service = app(SensitiveService::class);

        $count = $service->batchImport(['暴力']);

        $this->assertSame(0, $count);
    }

    public function test_contains_works_with_multiple_sensitive_words(): void
    {
        Sensitive::factory()->create(['keywords' => '暴力']);
        Sensitive::factory()->create(['keywords' => '色情']);
        Sensitive::factory()->create(['keywords' => '赌博']);

        $service = app(SensitiveService::class);

        $this->assertTrue($service->contains('赌博危害大'));
        $this->assertTrue($service->contains('色情内容'));
        $this->assertTrue($service->contains('暴力电影'));
    }

    public function test_greedy_matching_longest_word(): void
    {
        Sensitive::factory()->create(['keywords' => '敏感']);
        Sensitive::factory()->create(['keywords' => '敏感词']);

        $service = app(SensitiveService::class);

        $result = $service->filter('包含敏感词的文本');

        // 贪婪匹配应该匹配最长的"敏感词"
        $this->assertStringContainsString('***', $result);
    }
}
