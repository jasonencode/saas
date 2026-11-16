<?php

namespace App\Livewire\Filament;

use App\Services\System\EventLogService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class ClearCache extends Component
{
    /**
     * 清除缓存
     *
     * 仅平台超管可执行；执行结果写入事件日志，便于审计。
     */
    public function clear(): void
    {
        $user = Filament::auth()->user();

        abort_unless($user?->isAdministrator() ?? false, 403);

        Artisan::call('optimize:clear');
        Artisan::call('modelCache:clear');

        service(EventLogService::class)->write(
            'system.cache_cleared',
            '清除应用缓存',
            $user,
        );

        $this->dispatch('close-modal', id: 'confirm-clear-cache');

        Notification::make()
            ->success()
            ->title('缓存已清除')
            ->body('应用缓存及模型缓存已成功清除！')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.filament.clear-cache');
    }
}
