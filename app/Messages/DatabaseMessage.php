<?php

namespace App\Messages;

use Illuminate\Support\Str;

/**
 * 数据库消息
 *
 * 使用示例:
 * ```php
 *  DatabaseMessage::make()
 *     ->setTitle()
 *     ->setTemplate('【{name}】的通知')
 *     ->setArgs(['name' => '张三']);
 * ```
 */
class DatabaseMessage
{
    protected ?string $title = null;

    protected ?string $body = null;

    protected ?string $template = null;

    protected array $args = [];

    public function __construct()
    {
    }

    public static function make(): DatabaseMessage
    {
        return new self();
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * 设置消息使用的模板
     *
     * @param  string  $template
     * @return $this
     */
    public function template(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * 设置消息模板解析的文件
     *
     * @param  array  $args
     * @return $this
     */
    public function args(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    /**
     * 通过这个魔术方法，给到发送数据库通知需要的 data 的值
     *   存储到数据库的时候，他会在这取data，应该是框架定义的一种模式
     *
     * @param  string  $name
     * @return string[]|null
     */
    public function __get(string $name)
    {
        if ($name == 'data') {
            return [
                'title' => $this->getTitle(),
                'body' => $this->getBody(),
            ];
        } else {
            return null;
        }
    }

    protected function getTitle(): ?string
    {
        return $this->title;
    }

    protected function getBody(): string
    {
        return $this->body.$this->parseTemplate();
    }

    /**
     * 解析模板
     *
     * @return string
     */
    protected function parseTemplate(): string
    {
        if (!$this->template) {
            return '';
        }

        if (Str::contains($this->template, '%s')) {
            return sprintf($this->template, ...array_values($this->args));
        }

        if (preg_match('/\{\S*}/U', $this->template)) {
            foreach ($this->args as $key => $value) {
                $this->template = Str::replace('{'.$key.'}', $value, $this->template);
            }
        }

        return $this->template ?? '';
    }
}
