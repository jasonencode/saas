<?php

namespace App\Policies\System;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use Filament\Actions\Exports\Models\Export;

class ExportPolicy extends Policy
{
    protected string $modelName = '数据导出';

    protected string $groupName = '系统管理';

    protected int $platform = 1;

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('文件下载', type: PolicyType::Page)]
    public function view(Authenticatable $user, Export $export): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user, Export $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('下载XLSX', type: PolicyType::Button)]
    public function downloadExportXlsx(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('下载CSV', type: PolicyType::Button)]
    public function downloadExportCsv(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
