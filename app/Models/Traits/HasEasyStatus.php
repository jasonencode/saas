<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 简易状态特征
 *
 * @property bool $status
 */
trait HasEasyStatus
{
    /**
     * 初始化状态特征
     *
     * @return void
     */
    public function initializeHasEasyStatus(): void
    {
        $this->mergeCasts([
            $this->getStatusField() => 'boolean',
        ]);
    }

    /**
     * 获取状态字段名
     *
     * @return string
     */
    protected function getStatusField(): string
    {
        return $this->statusField ?? 'status';
    }

    /**
     * 切换状态
     *
     * @return bool
     */
    public function toggleStatus(): bool
    {
        return $this->isEnabled() ? $this->disable() : $this->enable();
    }

    /**
     * 是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->{$this->getStatusField()};
    }

    /**
     * 禁用
     *
     * @return bool
     */
    public function disable(): bool
    {
        $this->{$this->getStatusField()} = false;

        return $this->save();
    }

    /**
     * 启用
     *
     * @return bool
     */
    public function enable(): bool
    {
        $this->{$this->getStatusField()} = true;

        return $this->save();
    }

    /**
     * 是否可以启用
     *
     * @return bool
     */
    public function canEnable(): bool
    {
        return $this->isDisabled();
    }

    /**
     * 是否已禁用
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * 是否可以禁用
     *
     * @return bool
     */
    public function canDisable(): bool
    {
        return $this->isEnabled();
    }

    /**
     * 启用状态作用域
     *
     * @param  Builder  $query
     * @return Builder
     */
    #[Scope]
    protected function ofEnabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), true);
    }

    /**
     * 禁用状态作用域
     *
     * @param  Builder  $query
     * @return Builder
     */
    #[Scope]
    protected function ofDisabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), false);
    }
}
