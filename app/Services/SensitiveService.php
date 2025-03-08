<?php

namespace App\Services;

use App\Models\Sensitive;
use Illuminate\Support\Facades\Cache;

class SensitiveService
{
    private const CACHE_TTL = 86400;

    private array $tree = [];

    private bool $isInitialized = false;

    private string $replaceChar;

    public function __construct(string $replaceChar = '*')
    {
        $this->replaceChar = $replaceChar;
    }

    public function filter(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        $this->initialize();

        // 标准化输入文本
        $text = $this->normalizeString($text);
        $chars = mb_str_split($text);
        $len = mb_strlen($text);
        $result = $chars;

        for ($i = 0; $i < $len; $i++) {
            $current = $this->tree;
            $wordLen = 0;

            for ($j = $i; $j < $len; $j++) {
                if (!isset($current[$chars[$j]])) {
                    break;
                }

                $current = $current[$chars[$j]];
                $wordLen++;

                if (isset($current['end'])) {
                    $replaceStr = str_repeat($this->replaceChar, $wordLen);
                    for ($k = 0; $k < $wordLen; $k++) {
                        $result[$i + $k] = mb_substr($replaceStr, $k, 1);
                    }
                    break;
                }
            }
        }

        return implode('', $result);
    }

    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->tree = Cache::remember('sensitive_words_tree', now()->addSeconds(self::CACHE_TTL), function() {
            $words = Sensitive::pluck('keywords')->toArray();

            return $this->buildTree($words);
        });

        $this->isInitialized = true;
    }

    private function buildTree(array $words): array
    {
        $tree = [];

        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }

            // 确保以 UTF-8 编码处理中文
            $word = $this->normalizeString($word);
            $chars = mb_str_split($word);
            $current = &$tree;

            foreach ($chars as $char) {
                if (!isset($current[$char])) {
                    $current[$char] = [];
                }
                $current = &$current[$char];
            }

            $current['end'] = true;
        }

        return $tree;
    }

    /**
     * 标准化字符串
     * - 移除多余空格
     * - 确保 UTF-8 编码
     */
    private function normalizeString(string $string): string
    {
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string));
        }

        return trim($string);
    }

    public function contains(string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $this->initialize();
        $text = $this->normalizeString($text);
        $chars = mb_str_split($text);
        $len = mb_strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $current = $this->tree;
            for ($j = $i; $j < $len; $j++) {
                if (!isset($current[$chars[$j]])) {
                    break;
                }

                $current = $current[$chars[$j]];
                if (isset($current['end'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function find(string $text): array
    {
        if (empty($text)) {
            return [];
        }
        $this->initialize();
        $chars = mb_str_split($text);
        $len = count($chars);
        $words = [];
        for ($i = 0; $i < $len; $i++) {
            $current = $this->tree;
            $word = '';

            for ($j = $i; $j < $len; $j++) {
                if (!isset($current[$chars[$j]])) {
                    break;
                }
                $word .= $chars[$j];
                $current = $current[$chars[$j]];
                if (isset($current['end'])) {
                    $words[] = $word;
                    break;
                }
            }
        }

        return array_unique($words);
    }
}
