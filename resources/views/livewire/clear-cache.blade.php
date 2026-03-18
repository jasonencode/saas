<div>
    <x-filament::icon-button
            icon="heroicon-o-trash"
            color="gray"
            label="清除缓存"
            wire:click="$dispatch('open-modal', { id: 'clear-cache-confirmation' })"
            tooltip="清除模型缓存"
    />

    <x-filament::modal id="clear-cache-confirmation" alignment="center" icon="heroicon-o-information-circle"
                       icon-color="warning">
        <x-slot name="heading">
            清除缓存
        </x-slot>

        <x-slot name="description">
            您确定要清除所有模型缓存吗？这可能会影响系统性能。
        </x-slot>

        <x-slot name="footer">
            <x-filament::button wire:click="clear" wire:target="clear" class="w-full">
                确认清除
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
