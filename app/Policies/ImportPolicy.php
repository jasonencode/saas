<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use Filament\Actions\Imports\Models\Import;

class ImportPolicy extends Policy
{
    protected string $modelName = '数据导入';

    #[PolicyName('显示', '')]
    public function view(Authenticatable $user, Import $import): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}