<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\TextColumn;

/**
 * JSON 数据表格列
 *
 * 用于在表格中展示 JSON 格式的数据，支持关联数组和索引数组。
 * 数据默认折叠显示，点击后展开完整内容。
 */
class JsonColumn extends TextColumn
{
    /**
     * 格式化类型
     */
    protected string|Closure|null $format = null;

    /**
     * 是否显示数组的 key
     */
    protected bool|Closure $showKeys = true;

    /**
     * 折叠时显示的行数
     */
    protected int|Closure $collapsedLines = 2;

    /**
     * 创建列实例
     *
     * @param  string|null  $name  列名/字段名
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }

    /**
     * 设置是否显示 key
     *
     * @param  bool|Closure  $showKeys  是否显示 key
     */
    public function showKeys(bool|Closure $showKeys = true): static
    {
        $this->showKeys = $showKeys;

        return $this;
    }

    /**
     * 设置折叠时显示的行数
     *
     * @param  int|Closure  $lines  折叠时显示的行数
     */
    public function collapsedLines(int|Closure $lines): static
    {
        $this->collapsedLines = $lines;

        return $this;
    }

    /**
     * 获取是否显示 key
     *
     * @return bool 是否显示 key
     */
    public function getShowKeys(): bool
    {
        return (bool) $this->evaluate($this->showKeys);
    }

    /**
     * 获取折叠时显示的行数
     *
     * @return int 折叠时显示的行数
     */
    public function getCollapsedLines(): int
    {
        return (int) $this->evaluate($this->collapsedLines);
    }

    /**
     * 格式化状态数据
     *
     * 将 JSON 数据格式化为可读的字符串格式。
     * 关联数组显示为 "key: value" 格式，索引数组直接显示值。
     * 每条数据占一行显示。
     *
     * @param  mixed  $state  原始数据（数组或 JSON 字符串）
     *
     * @throws \JsonException
     *
     * @return string 格式化后的字符串
     */
    public function formatState(mixed $state): string
    {
        if (empty($state)) {
            return '-';
        }

        $array = is_array($state) ? $state : json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR) ?? [];

        if (empty($array)) {
            return '-';
        }

        $showKeys = $this->getShowKeys();

        return collect($array)->map(fn ($value, $key) => match (true) {
            $showKeys && !is_int($key) => $key.': '.(is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : (string) $value),
            is_array($value) => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        })->implode("\n");
    }

    /**
     * 渲染为 HTML
     *
     * @throws \JsonException
     *
     * @return string HTML 字符串
     */
    public function toHtml(): string
    {
        $state = $this->getState();
        $formattedState = $this->formatState($state);
        $collapsedLines = $this->getCollapsedLines();

        $attributes = $this->getExtraAttributeBag()
            ->class(['fi-ta-text fi-ta-text-item fi-size-sm overflow-hidden']);

        $lines = explode("\n", $formattedState);
        $lineCount = count($lines);
        $isExpandable = $lineCount > $collapsedLines;
        $collapsedContent = implode("\n", array_slice($lines, 0, $collapsedLines));

        if (!$isExpandable) {
            return '<div '.$attributes->toHtml().'>'
                .'<div class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 dark:text-white sm:text-sm sm:leading-6 whitespace-pre-wrap">'
                .e($formattedState)
                .'</div>'
                .'</div>';
        }

        $escapedCollapsed = e($collapsedContent);
        $escapedFull = e($formattedState);

        return '<div x-data="{ expanded: false }" '.$attributes->toHtml().'>'
            .'<div class="relative block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 dark:text-white sm:text-sm sm:leading-6 whitespace-pre-wrap">'
            .'<div x-show="!expanded" x-cloak class="overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: '.$collapsedLines.'; -webkit-box-orient: vertical;">'
            .$escapedCollapsed
            .'</div>'
            .'<div x-show="expanded" x-cloak>'
            .$escapedFull
            .'</div>'
            .'</div>'
            .'<div class="px-3 pb-1">'
            .'<button x-on:click="expanded = !expanded" type="button" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">'
            .'<span x-show="!expanded" x-cloak">展开全部</span>'
            .'<span x-show="expanded" x-cloak">收起</span>'
            .'<svg x-show="!expanded" x-cloak class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>'
            .'<svg x-show="expanded" x-cloak class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd" /></svg>'
            .'</button>'
            .'</div>'
            .'</div>';
    }
}
