<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use Filament\Actions\Exports\Models\Export;

class ExportPolicy extends Policy
{
    protected string $modelName = '数据导出';

    #[PolicyName('文件下载', '')]
    public function view(Authenticatable $user, Export $export): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}