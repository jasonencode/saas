<?php

namespace App\Contracts\Notification;

/**
 * 极光推送消息构建器
 *
 * 使用示例:
 *  JPushMessage::make()
 *     ->alert('您有新的订单待处理')
 *     ->title('订单通知')
 *     ->extras(['order_no' => 'A1001']);
 */
class JPushMessage
{
    /**
     * 推送平台
     *
     * @var array<int, string>
     */
    protected array $platforms = ['android', 'ios'];

    /**
     * 别名列表（未设置时由通道回退为当前用户 ID）
     *
     * @var array<int, string>
     */
    protected array $aliases = [];

    /**
     * 设备标识列表
     *
     * @var array<int, string>
     */
    protected array $registrationIds = [];

    /**
     * 通知提示文案
     */
    protected string $alert = '';

    /**
     * 通知标题（Android 使用，iOS 忽略）
     */
    protected ?string $title = null;

    /**
     * 附加数据
     *
     * @var array<string, mixed>
     */
    protected array $extras = [];

    /**
     * 推送可选参数
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

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
     * 设置通知提示文案
     *
     * @param  string  $alert  提示文案
     *
     * @return static 当前实例
     */
    public function alert(string $alert): static
    {
        $this->alert = $alert;

        return $this;
    }

    /**
     * 设置通知标题
     *
     * @param  string|null  $title  通知标题
     *
     * @return static 当前实例
     */
    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置推送平台
     *
     * @param  array<int, string>|string  $platforms  平台（all/android/ios），可为数组
     *
     * @return static 当前实例
     */
    public function platform(array|string $platforms): static
    {
        $this->platforms = is_array($platforms) ? $platforms : [$platforms];

        return $this;
    }

    /**
     * 设置别名
     *
     * @param  array<int, string>|string  $aliases  别名（建议使用用户 ID），可为数组
     *
     * @return static 当前实例
     */
    public function alias(array|string $aliases): static
    {
        $this->aliases = is_array($aliases) ? $aliases : [$aliases];

        return $this;
    }

    /**
     * 设置设备标识
     *
     * @param  array<int, string>  $registrationIds  设备 registration_id 列表
     *
     * @return static 当前实例
     */
    public function registrationIds(array $registrationIds): static
    {
        $this->registrationIds = $registrationIds;

        return $this;
    }

    /**
     * 设置附加数据
     *
     * @param  array<string, mixed>  $extras  附加数据，客户端可读取用于跳转
     *
     * @return static 当前实例
     */
    public function extras(array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * 设置推送可选参数
     *
     * 支持 sendno、time_to_live、apns_production、big_push_duration 等，
     * 其中 apns_production 由通道按配置注入，不设置时极光默认走开发环境。
     *
     * @param  array<string, mixed>  $options  可选参数
     *
     * @return static 当前实例
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * 获取推送平台
     *
     * @return array<int, string> 推送平台
     */
    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    /**
     * 获取别名列表
     *
     * @return array<int, string> 别名列表
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * 获取设备标识列表
     *
     * @return array<int, string> 设备标识列表
     */
    public function getRegistrationIds(): array
    {
        return $this->registrationIds;
    }

    /**
     * 获取通知提示文案
     *
     * @return string 通知提示文案
     */
    public function getAlert(): string
    {
        return $this->alert;
    }

    /**
     * 获取通知标题
     *
     * @return string|null 通知标题
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * 获取附加数据
     *
     * @return array<string, mixed> 附加数据
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * 获取推送可选参数
     *
     * @return array<string, mixed> 推送可选参数
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
