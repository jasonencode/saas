<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Roles\Schemas;

use App\Enums\PolicyPlatform;
use App\Factories\PolicyPermission;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
                self::buildPermissionComponent(),
            ]);
    }

    /**
     * 获取Tab组件，用tab组件来区分各模块的权限
     *
     * @return Tabs
     */
    protected static function buildPermissionComponent(): Tabs
    {
        return Tabs::make('Tabs')
            ->columnSpanFull()
            ->id('backend-permissions')
            ->tabs(self::getPolicyGroupTabs())
            ->hiddenOn('create');
    }

    protected static function getPolicyGroupTabs(): array
    {
        $list = PolicyPermission::tree(PolicyPlatform::Tenant)
            ->groupBy('group');
        $tabs = [];

        foreach ($list as $name => $item) {
            $tabs[] = self::getModulePolicies($name, $item);
        }

        return $tabs;
    }

    protected static function getModulePolicies(string $name, Collection $item): Tab
    {
        return Tab::make($name)
            ->schema([
                Grid::make()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema(self::getResourceEntitiesSchema($item)),
            ]);
    }

    protected static function getResourceEntitiesSchema(Collection $item): ?array
    {
        return $item->map(function (array $entity) {
            return Section::make($entity['name'])
                ->compact()
                ->columnSpan(1)
                ->collapsible()
                ->schema([
                    self::getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected static function getCheckboxListFormComponent(string $method, array $options): CheckboxList
    {
        return CheckboxList::make('permissions['.$method.']')
            ->label('权限')
            ->gridDirection('row')
            ->bulkToggleable()
            ->columns()
            ->options(array_column($options, 'name', 'method'))
            ->descriptions(array_column($options, 'description', 'method'));
    }
}
