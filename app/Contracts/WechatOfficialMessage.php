<?php

namespace App\Contracts;

/**
 * 数据库消息
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

    public static function make(): WechatOfficialMessage
    {
        return new self();
    }

    public function templateId(string $templateId): static
    {
        $this->template_id = $templateId;

        return $this;
    }

    public function openId(string $openId): static
    {
        $this->openId = $openId;

        return $this;
    }

    public function url(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function miniprogram(string $appid, string $pagepath): static
    {
        $this->miniprogram = [
            'appid' => $appid,
            'pagepath' => $pagepath,
        ];

        return $this;
    }

    public function payload(string $key, string $value): static
    {
        $this->data[$key] = [
            'value' => $value,
        ];

        return $this;
    }

    public function getTemplateId(): string
    {
        return $this->template_id;
    }

    public function getOpenId(): string
    {
        return $this->openId;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMiniprogram(): array
    {
        return $this->miniprogram;
    }
}
