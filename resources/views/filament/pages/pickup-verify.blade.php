<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            核销码核销
        </x-slot>

        <div class="mt-2 flex gap-3">
            <x-filament::input.wrapper>
                <x-filament::input
                        id="pickup-code"
                        type="text"
                        wire:model="pickupCode"
                        wire:keydown.enter="search"
                        placeholder="请输入核销码"
                        autofocus
                />
            </x-filament::input.wrapper>
            <x-filament::button
                    tag="button"
                    wire:click="search"
                    color="primary"
            >
                查询订单
            </x-filament::button>
            {{ $this->verifyAction }}
        </div>

        @if ($this->error)
            <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $this->error }}</p>
        @endif
    </x-filament::section>

    <div class="space-y-6">
        {{-- 下方动态展示 --}}
        @if ($this->order)
            {{ $this->infolist }}

            {{ $this->table }}
        @endif
    </div>
</x-filament-panels::page>
