<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Roles\Schemas;

use App\Enums\System\PolicyPlatform;
use App\Models\System\Administrator;
use App\Support\PolicyPermission\PolicyPermission;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
     */
    protected static function buildPermissionComponent(): Schemas\Components\Tabs
    {
        return Schemas\Components\Tabs::make('Tabs')
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
                Schemas\Components\Grid::make(1)
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema(self::getResourceEntitiesSchema($item)),
            ]);
    }

    protected static function getResourceEntitiesSchema(Collection $item): ?array
    {
        return $item->map(function (array $entity) {
            return Schemas\Components\Section::make($entity['name'])
                ->compact()
                ->collapsible()
                ->schema([
                    self::getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected static function getCheckboxListFormComponent(string $method, array $options): CheckboxList
    {
        $options = self::grantableOptions($method, $options);

        return CheckboxList::make('permissions.'.$method)
            ->label('权限')
            ->gridDirection('row')
            ->bulkToggleable()
            ->columns()
            ->options(
                collect($options)
                    ->mapWithKeys(fn (array $opt): array => [Str::after($opt['method'], '@') => $opt['name']])
                    ->toArray()
            )
            ->descriptions(
                collect($options)
                    ->mapWithKeys(fn (array $opt): array => [Str::after($opt['method'], '@') => $opt['description']])
                    ->filter()
                    ->toArray()
            );
    }

    /**
     * 仅展示操作者自身已拥有的权限，防止借角色编辑给自己提权
     *
     * @param  string  $policy  策略类名
     * @param  array<int, array<string, mixed>>  $options  权限项
     *
     * @return array<int, array<string, mixed>> 可授予的权限项
     */
    protected static function grantableOptions(string $policy, array $options): array
    {
        $user = Filament::auth()->user();

        if (!$user instanceof Administrator) {
            return [];
        }

        if ($user->isAdministrator()) {
            return $options;
        }

        return array_values(array_filter(
            $options,
            static fn (array $option): bool => $user->hasPermission($policy, Str::after($option['method'], '@')),
        ));
    }
}
