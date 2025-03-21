<?php

namespace App\Filament\Backend\Clusters\Settings\Pages;

use App\Contracts\ModuleSetting;
use App\Filament\Backend\Clusters\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

/**
 * @property Form $form
 */
class Setting extends Page
{
    use InteractsWithFormActions;

    protected static string $view = 'admin.pages.config';

    protected static ?string $navigationLabel = '模块配置';

    protected static ?string $title = '模块配置';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 101;

    protected static ?string $navigationGroup = '模块';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return userCan('viewAny', \App\Models\Setting::class);
    }

    public function mount(): void
    {
        $this->form->fill(
            $this->getModel()
                ->get()
                ->groupBy('module')
                ->map(fn($item) => $item->pluck('value', 'key'))
                ->toArray()
        );
    }

    protected function getModel(): \App\Models\Setting
    {
        return new \App\Models\Setting();
    }

    public function save(): void
    {
        $data = $this->form->getState();

        DB::transaction(function() use ($data) {
            foreach ($data as $module => $configs) {
                foreach ($configs as $key => $value) {
                    $this->getModel()->updateOrCreate([
                        'module' => $module,
                        'key' => $key,
                    ], [
                        'value' => $value,
                    ]);
                }
            }

            return true;
        });

        Notification::make()
            ->success()
            ->title('配置保存成功')
            ->send();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    Tabs::make('Tabs')
                        ->tabs($this->getConfigForms())
                        ->id('setting-tabs'),
                ])
                ->operation('edit')
                ->statePath('data'),
        ];
    }

    protected function getConfigForms(): array
    {
        return collect(Module::allEnabled())
            ->filter(fn($module) => $this->hasSettingFile($module))
            ->map(fn($module) => $this->createSettingInstance($module))
            ->filter()
            ->map(fn(ModuleSetting $instance) => $instance->tab())
            ->values()
            ->toArray();
    }

    private function hasSettingFile(object $module): bool
    {
        return file_exists(module_path($module->getName(), 'app/Filament/Setting.php'));
    }

    private function createSettingInstance(object $module): ?ModuleSetting
    {
        $class = sprintf('Modules\\%s\\Filament\\Setting', $module->getName());

        return class_exists($class)
            ? tap(new $class, fn($instance) => $instance instanceof ModuleSetting ? $instance : null)
            : null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('保存配置')
            ->submit('save')
            ->visible(userCan('save', \App\Models\Setting::class));
    }
}
