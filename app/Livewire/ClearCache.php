<?php

namespace App\Livewire;

use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class ClearCache extends Component
{
    public function clear(): void
    {
        try {
            Artisan::call('modelCache:clear');

            Notification::make()
                ->title('缓存清除成功')
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'clear-cache-confirmation');
        } catch (Exception $e) {
            Notification::make()
                ->title('缓存清除失败')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render(): View
    {
        return view('livewire.clear-cache');
    }
}
