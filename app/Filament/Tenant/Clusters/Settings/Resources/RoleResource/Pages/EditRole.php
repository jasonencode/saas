<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages;

use App\Factories\PolicyPermission;
use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource;
use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function getTitle(): string|Htmlable
    {
        return sprintf('角色：《%s》详情', $this->getRecord()->name);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Forms\Components\Section::make('权限配置')
                    ->schema([
                        $this->buildPermissionComponent(),
                    ]),
            ]);
    }

    protected function buildPermissionComponent()
    {
        return Forms\Components\Grid::make()
            ->columns(['default' => 1, 'sm' => 2, 'xl' => 3, '2xl' => 4])
            ->schema($this->getResourceEntitiesSchema([]));
    }

    protected function getResourceEntitiesSchema(): ?array
    {
        $list = PolicyPermission::tree();

        return $list->get('Tenant')->map(function(array $entity) {
            return Forms\Components\Section::make($entity['name'])
                ->compact()
                ->columnSpan(1)
                ->collapsible()
                ->schema([
                    $this->getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected function getCheckboxListFormComponent(string $method, array $options): Forms\Components\Component
    {
        return Forms\Components\CheckboxList::make('permissions['.$method.']')
            ->label('')
            ->gridDirection('row')
            ->bulkToggleable()
            ->columns()
            ->options(array_column($options, 'name', 'method'))
            ->descriptions(array_column($options, 'description', 'method'));
    }

    protected function getActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check-circle')
                ->label('保存编辑')
                ->formId('form'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(AdminRole|Model $record, array $data): Model
    {
        $record->update(Arr::only($data, ['name', 'description']));
        $record->permissions()->delete();

        foreach ($data as $key => $items) {
            if (Str::startsWith($key, 'permissions')) {
                preg_match('/permissions\[(\S+)]/', $key, $match);
                if (class_exists($match[1])) {
                    foreach ($items as $item) {
                        $record->permissions()->create([
                            'role_id' => $record->id,
                            'policy' => $match[1],
                            'method' => $item,
                        ]);
                    }
                }
            }
        }

        return $record;
    }

    /**
     * 填充表单
     *
     * @param  array  $data
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = AdminRolePermission::select('policy')
            ->distinct()
            ->where('role_id', $this->getRecord()->getKey())
            ->get();

        foreach ($permissions as $permission) {
            $methods = AdminRolePermission::where('role_id', $this->getRecord()->getKey())
                ->where('policy', $permission['policy'])
                ->select('method')
                ->pluck('method')
                ->toArray();

            $data['permissions['.$permission['policy'].']'] = $methods;
        }

        return $data;
    }
}
