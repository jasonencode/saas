<?php

namespace App\Contracts\Notification;

/**
 * 钉钉消息构建器
 *
 * 使用示例:
 *  DingTalkMessage::make()
 *     ->userId('manager1234')
 *     ->markdown('订单通知', "### 您有新的订单\n\n订单号：A1001");
 */
class DingTalkMessage
{
    /**
     * 接收人钉钉 userId 列表
     *
     * @var array<int, string>
     */
    protected array $userIds = [];

    /**
     * 消息类型
     */
    protected string $msgKey = 'sampleText';

    /**
     * 消息内容参数
     *
     * @var array<string, mixed>
     */
    protected array $msgParam = [];

    /**
     * 创建消息实例
     *
     * @return self 消息实例
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * 设置单个接收人
     *
     * @param  string  $userId  钉钉 userId
     *
     * @return static 当前实例
     */
    public function userId(string $userId): static
    {
        $this->userIds = [$userId];

        return $this;
    }

    /**
     * 批量设置接收人
     *
     * @param  array<int, string>  $userIds  钉钉 userId 列表（单次最多 100 个）
     *
     * @return static 当前实例
     */
    public function userIds(array $userIds): static
    {
        $this->userIds = array_values(array_unique($userIds));

        return $this;
    }

    /**
     * 设置文本消息
     *
     * @param  string  $content  消息文本
     *
     * @return static 当前实例
     */
    public function text(string $content): static
    {
        return $this->payload('sampleText', ['content' => $content]);
    }

    /**
     * 设置 Markdown 消息
     *
     * @param  string  $title  消息标题
     * @param  string  $text  Markdown 正文
     *
     * @return static 当前实例
     */
    public function markdown(string $title, string $text): static
    {
        return $this->payload('sampleMarkdown', [
            'title' => $title,
            'text' => $text,
        ]);
    }

    /**
     * 设置原始消息内容
     *
     * @param  string  $msgKey  消息类型（sampleText/sampleMarkdown/sampleLink/sampleActionCard）
     * @param  array<string, mixed>  $msgParam  消息内容参数，需与 msgKey 对应
     *
     * @return static 当前实例
     */
    public function payload(string $msgKey, array $msgParam): static
    {
        $this->msgKey = $msgKey;
        $this->msgParam = $msgParam;

        return $this;
    }

    /**
     * 获取接收人列表
     *
     * @return array<int, string> 钉钉 userId 列表
     */
    public function getUserIds(): array
    {
        return $this->userIds;
    }

    /**
     * 获取消息类型
     *
     * @return string 消息类型
     */
    public function getMsgKey(): string
    {
        return $this->msgKey;
    }

    /**
     * 获取消息内容参数
     *
     * @return array<string, mixed> 消息内容参数
     */
    public function getMsgParam(): array
    {
        return $this->msgParam;
    }
}
