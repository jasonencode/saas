<?php

namespace App\Admin\Clusters\Settings\Resources\AdminRoleResource\Pages;

use App\Admin\Clusters\Settings\Resources\AdminRoleResource;
use App\Factories\PolicyPermission;
use App\Models\AdminRole;
use App\Models\AdminRolePermission;
use Arr;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = AdminRoleResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('角色名称')
                ->required(),
            Textarea::make('description')
                ->label('描述')
                ->rows(4)
                ->columnSpanFull(),
            $this->buildPermissionComponent(),
        ]);
    }

    /**
     * 获取Tab组件，用tab组件来区分各模块的权限
     *
     * @return Tabs
     */
    protected function buildPermissionComponent(): Tabs
    {
        return Tabs::make('Tabs')
            ->columnSpanFull()
            ->tabs($this->getPolicyGroupTabs());
    }

    /**
     * 构建Tab
     *
     * @return array<Tabs\Tab>
     */
    protected function getPolicyGroupTabs(): array
    {
        $list = PolicyPermission::tree();
        $tabs = [];

        foreach ($list as $name => $item) {
            $tabs[] = $this->getModulePolicies($name, $item);
        }

        return $tabs;
    }

    protected function getModulePolicies(string $name, Collection $item)
    {
        return Tabs\Tab::make($name)
            ->schema([
                Grid::make()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema($this->getResourceEntitiesSchema($item)),
            ]);
    }

    protected function getResourceEntitiesSchema(Collection $item): ?array
    {
        return $item->map(function (array $entity) {
            return Section::make($entity['name'])
                ->compact()
                ->columnSpan(1)
                ->collapsible()
                ->schema([
                    $this->getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected function getCheckboxListFormComponent(string $method, array $options): Component
    {
        return CheckboxList::make('permissions['.$method.']')
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}