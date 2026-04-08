<?php

namespace App\Contracts\Notification;

/**
 * 微信小程序消息接口
 */
interface WechatMiniMessage
{
    /**
     * 获取模板ID
     */
    public function getTemplateId(): string;

    /**
     * 获取消息数据
     */
    public function getData(): array;

    /**
     * 获取页面路径
     */
    public function getPage(): ?string;

    /**
     * 获取用户OpenID
     */
    public function getToUser(): string;
}
