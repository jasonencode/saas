<div>
    <x-filament::icon-button
            icon="heroicon-o-trash"
            color="gray"
            label="清除缓存"
            x-on:click="$dispatch('open-modal', {id: 'confirm-clear-cache'})"
            tooltip="清除应用缓存"
    />

    <x-filament::modal id="confirm-clear-cache" width="sm">
        <x-slot name="heading">确认清除缓存</x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            确定要清除所有应用缓存吗？此操作将清除优化、视图、路由、配置等缓存文件。
        </p>

        <x-slot name="footerActions">
            <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', {id: 'confirm-clear-cache'})"
            >
                取消
            </x-filament::button>

            <x-filament::button
                    color="danger"
                    wire:click="clear"
            >
                确认清除
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
