<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Schemas;

use App\Enums\System\PolicyPlatform;
use App\Enums\System\PolicyType;
use App\Models\System\Administrator;
use App\Support\PolicyPermission\PolicyPermission;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
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
            ->columns(4)
            ->components(self::getResourceEntitiesSchema($item));
    }

    protected static function getResourceEntitiesSchema(Collection $item): ?array
    {
        return $item->map(function (array $entity) {
            return Schemas\Components\Section::make($entity['name'])
                ->description(PolicyPlatform::parseLabel($entity['platform']))
                ->compact()
                ->collapsible()
                ->schema([
                    self::getCheckboxListFormComponent($entity['method'], $entity['children']),
                ]);
        })->toArray();
    }

    protected static function getCheckboxListFormComponent(string $method, array $options): Forms\Components\CheckboxList
    {
        $options = self::grantableOptions($method, $options);

        return Forms\Components\CheckboxList::make('permissions.'.$method)
            ->label('权限')
            ->gridDirection('row')
            ->bulkToggleable()
            ->columns()
            ->options(
                collect($options)
                    ->mapWithKeys(fn (array $opt) => [
                        Str::after($opt['method'], '@') => self::renderPolicyOptionLabel($opt),
                    ])
                    ->toArray()
            )
            ->descriptions(
                collect($options)
                    ->mapWithKeys(fn (array $opt) => [Str::after($opt['method'], '@') => $opt['description']])
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
