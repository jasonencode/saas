<?php

namespace App\Livewire\Filament;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class ClearCache extends Component
{
    public function clear(): void
    {
        Artisan::call('optimize:clear');
        Artisan::call('modelCache:clear');

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
