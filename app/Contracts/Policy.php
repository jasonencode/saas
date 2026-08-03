<?php

namespace App\Contracts;

use App\Enums\System\PolicyPlatform;
use App\Models\System\Administrator;
use Illuminate\Foundation\Auth\User;

/**
 * 权限策略基类
 */
abstract class Policy
{
    protected string $modelName = '鉴权';

    protected string $groupName = '系统权限';

    protected int $platform = PolicyPlatform::Both->value;

    /**
     * 是否放行权限检查
     *
     * @param  User  $user  当前用户
     *
     * @return bool|null 放行结果（true 直接放行，null 继续判断）
     */
    public function before(User $user): ?bool
    {
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }

    /**
     * 获取模型名称
     *
     * @return string 模型名称
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * 获取分组名称
     *
     * @return string 分组名称
     */
    public function getGroupName(): string
    {
        return $this->groupName;
    }

    /**
     * 获取平台值
     *
     * @return int 平台值
     */
    public function getPlatform(): int
    {
        return $this->platform;
    }
}
