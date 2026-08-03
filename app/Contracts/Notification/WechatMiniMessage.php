<?php

namespace App\Contracts\Notification;

/**
 * 微信小程序消息接口
 */
interface WechatMiniMessage
{
    /**
     * 获取模板ID
     *
     * @return string 模板 ID
     */
    public function getTemplateId(): string;

    /**
     * 获取消息数据
     *
     * @return array 消息数据
     */
    public function getData(): array;

    /**
     * 获取页面路径
     *
     * @return string|null 页面路径
     */
    public function getPage(): ?string;

    /**
     * 获取用户OpenID
     *
     * @return string 用户 OpenID
     */
    public function getToUser(): string;
}
