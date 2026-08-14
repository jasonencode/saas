<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            核销码核销
        </x-slot>

        <div class="mb-3 flex gap-3">
            <x-filament::input.wrapper>
                <x-filament::input
                        id="pickup-code"
                        type="text"
                        wire:model="pickupCode"
                        wire:keydown.enter="search"
                        placeholder="请输入核销码"
                        autofocus
                        required
                />
            </x-filament::input.wrapper>
            <x-filament::button
                    tag="button"
                    wire:click="search"
                    color="primary"
            >
                查询订单
            </x-filament::button>
            @if($this->order)
                <x-filament::button
                        tag="button"
                        wire:click="clear"
                        color="gray"
                        icon="heroicon-o-x-mark"
                >
                    清空
                </x-filament::button>
                {{ $this->verifyAction }}
            @endif
        </div>

        @if ($this->error)
            <x-filament::callout
                    icon="heroicon-o-exclamation-circle"
                    color="warning"
            >
                <x-slot name="description">
                    {{ $this->error }}
                </x-slot>
            </x-filament::callout>
        @endif
    </x-filament::section>

    <div class="flex flex-col gap-3">
        {{-- 下方动态展示 --}}
        @if ($this->order)
            {{ $this->infolist }}

            {{ $this->table }}
        @endif
    </div>
</x-filament-panels::page>
