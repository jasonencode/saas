<?php

namespace App\Contracts\Notification;

/**
 * 公众号消息构建器
 *
 * 使用示例:
 *  WechatOfficialMessage::make()
 *     ->openId($notifiable->open_id)
 *     ->templateId('H3n7xB2C0U_RgO1fzsyxI1WZ6KKlM6qmVEuCc6n55W0')
 *     ->url('https://cjango.com')
 *     ->payload('User', 'Jason')
 *     ->payload('Date', now())
 *     ->payload('Status', '已完成');
 */
class WechatOfficialMessage
{
    protected string $template_id;

    protected string $openId;

    protected ?string $url = null;

    protected array $data = [];

    protected array $miniprogram = [];

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
     * 设置模板 ID
     *
     * @param  string  $templateId  模板 ID
     *
     * @return static 当前实例
     */
    public function templateId(string $templateId): static
    {
        $this->template_id = $templateId;

        return $this;
    }

    /**
     * 设置用户 OpenID
     *
     * @param  string  $openId  用户 OpenID
     *
     * @return static 当前实例
     */
    public function openId(string $openId): static
    {
        $this->openId = $openId;

        return $this;
    }

    /**
     * 设置跳转链接
     *
     * @param  string|null  $url  跳转链接
     *
     * @return static 当前实例
     */
    public function url(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 设置小程序跳转
     *
     * @param  string  $appid  小程序 AppID
     * @param  string  $pagepath  小程序页面路径
     *
     * @return static 当前实例
     */
    public function miniprogram(string $appid, string $pagepath): static
    {
        $this->miniprogram = [
            'appid' => $appid,
            'pagepath' => $pagepath,
        ];

        return $this;
    }

    /**
     * 添加消息数据
     *
     * @param  string  $key  数据键名
     * @param  string  $value  数据值
     *
     * @return static 当前实例
     */
    public function payload(string $key, string $value): static
    {
        $this->data[$key] = [
            'value' => $value,
        ];

        return $this;
    }

    /**
     * 获取模板 ID
     *
     * @return string 模板 ID
     */
    public function getTemplateId(): string
    {
        return $this->template_id;
    }

    /**
     * 获取用户 OpenID
     *
     * @return string 用户 OpenID
     */
    public function getOpenId(): string
    {
        return $this->openId;
    }

    /**
     * 获取跳转链接
     *
     * @return string|null 跳转链接
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * 获取消息数据
     *
     * @return array 消息数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取小程序跳转配置
     *
     * @return array 小程序跳转配置
     */
    public function getMiniprogram(): array
    {
        return $this->miniprogram;
    }
}
