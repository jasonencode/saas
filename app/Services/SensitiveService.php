<?php

namespace App\Services;

use App\Models\Sensitive;
use Illuminate\Support\Facades\Cache;

class SensitiveService
{
    private const int CACHE_TTL = 86400;

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

        // 规范化输入：保留原文本用于替换，转换小写用于匹配
        $normalizedText = $this->normalizeString($text);
        $chars = mb_str_split($normalizedText);
        $len = mb_strlen($normalizedText);

        // 结果数组基于原始文本，以保留原始格式（如大小写）
        $resultChars = mb_str_split($text);

        for ($i = 0; $i < $len; $i++) {
            $current = $this->tree;
            $matchLen = 0;

            // 贪婪匹配：寻找从当前位置开始的最长匹配
            for ($j = $i; $j < $len; $j++) {
                if (!isset($current[$chars[$j]])) {
                    break;
                }

                $current = $current[$chars[$j]];

                if (isset($current['end'])) {
                    $matchLen = $j - $i + 1;
                }
            }

            // 如果找到匹配，执行替换并跳过已处理的字符
            if ($matchLen > 0) {
                $replaceStr = str_repeat($this->replaceChar, $matchLen);
                for ($k = 0; $k < $matchLen; $k++) {
                    $resultChars[$i + $k] = mb_substr($replaceStr, $k, 1);
                }
                $i += $matchLen - 1; // 跳过已匹配的字符
            }
        }

        return implode('', $resultChars);
    }

    private function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->tree = Cache::remember('sensitive_words_tree', now()->addSeconds(self::CACHE_TTL), function () {
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

            // 确保以 UTF-8 编码处理，并转为小写以忽略大小写
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
     * - 转换为小写 (便于忽略大小写匹配)
     */
    private function normalizeString(string $string): string
    {
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string));
        }

        return mb_strtolower(trim($string));
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
        // 使用原始文本获取实际匹配的词，但匹配逻辑基于小写
        $normalizedText = $this->normalizeString($text);
        $chars = mb_str_split($normalizedText);
        $originalChars = mb_str_split($text); // 用于提取原始词

        $len = count($chars);
        $words = [];

        for ($i = 0; $i < $len; $i++) {
            $current = $this->tree;
            $matchLen = 0;

            for ($j = $i; $j < $len; $j++) {
                if (!isset($current[$chars[$j]])) {
                    break;
                }
                $current = $current[$chars[$j]];
                if (isset($current['end'])) {
                    $matchLen = $j - $i + 1;
                }
            }

            if ($matchLen > 0) {
                // 提取原始文本中的词
                $word = '';
                for ($k = 0; $k < $matchLen; $k++) {
                    $word .= $originalChars[$i + $k];
                }
                $words[] = $word;
                $i += $matchLen - 1; // 跳过已匹配的字符
            }
        }

        return array_unique($words);
    }

    /**
     * 批量导入敏感词（自动去重）
     *
     * @param  array  $words
     * @return int 成功插入的数量
     */
    public function batchImport(array $words): int
    {
        if (empty($words)) {
            return 0;
        }

        // 1. 预处理：去空、去重
        $words = collect($words)
            ->filter()
            ->unique()
            ->values();

        if ($words->isEmpty()) {
            return 0;
        }

        // 2. 查找已存在的词（数据库层面去重）
        // 注意：如果数据量特别大，建议分批处理
        $existing = Sensitive::whereIn('keywords', $words)->pluck('keywords');

        // 3. 筛选出新词
        $newWords = $words->diff($existing);

        if ($newWords->isEmpty()) {
            return 0;
        }

        // 4. 组装插入数据
        $now = now();
        $insertData = $newWords->map(fn(string $word) => [
            'keywords' => $word,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        // 5. 批量插入
        Sensitive::insert($insertData);

        // 6. 手动清理缓存（insert 不会触发模型事件）
        Cache::delete('sensitive_words_tree');

        // 如果当前服务实例已初始化，清除本地缓存
        if ($this->isInitialized) {
            $this->tree = [];
            $this->isInitialized = false;
        }

        return count($insertData);
    }
}
