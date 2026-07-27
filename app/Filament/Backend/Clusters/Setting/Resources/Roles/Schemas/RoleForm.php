<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Schemas;

use App\Enums\System\PolicyPlatform;
use App\Enums\System\PolicyType;
use App\Support\PolicyPermission;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
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
        $list = PolicyPermission::tree(PolicyPlatform::Backend)
            ->groupBy('group');
        $tabs = [];

        foreach ($list as $name => $item) {
            $tabs[] = self::getModulePolicies($name, $item);
        }

        return $tabs;
    }

    protected static function getModulePolicies(string $name, Collection $item): Schemas\Components\Tabs\Tab
    {
        return Schemas\Components\Tabs\Tab::make($name)
            ->components([
                Schemas\Components\Grid::make()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 3, '2xl' => 4])
                    ->components(self::getResourceEntitiesSchema($item)),
            ]);
    }

    protected static function getResourceEntitiesSchema(Collection $item): ?array
    {
        return $item->map(function (array $entity) {
            return Schemas\Components\Section::make($entity['name'])
                ->description(PolicyPlatform::parseLabel($entity['platform']))
                ->compact()
                ->collapsible()
                ->columnSpan(1)
                ->components([
                    self::getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected static function getCheckboxListFormComponent(string $method, array $options): Forms\Components\CheckboxList
    {
        return Forms\Components\CheckboxList::make('permissions.'.$method)
            ->label('权限')
            ->gridDirection('row')
            ->bulkToggleable()
            ->columns()
            ->options(
                collect($options)
                    ->mapWithKeys(fn (array $opt) => [
                        $opt['method'] => self::renderPolicyOptionLabel($opt),
                    ])
                    ->toArray()
            )
            ->descriptions(
                collect($options)
                    ->mapWithKeys(fn (array $opt) => [$opt['method'] => $opt['description']])
                    ->filter()
                    ->toArray()
            );
    }

    /**
     * 渲染带类型颜色徽章的权限项标签
     */
    protected static function renderPolicyOptionLabel(array $opt): HtmlString
    {
        $type = PolicyType::from($opt['type']);
        $badgeClasses = match ($type) {
            PolicyType::Page => 'inline-flex items-center rounded-md bg-primary-50 px-1.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-200 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/20',
            PolicyType::Button => 'inline-flex items-center rounded-md bg-warning-50 px-1.5 py-0.5 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-200 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/20',
        };

        return new HtmlString(sprintf(
            '<span class="%s">%s</span> <span class="text-sm text-gray-700 dark:text-gray-300">%s</span>',
            $badgeClasses,
            $type->getLabel(),
            e($opt['name']),
        ));
    }
}
