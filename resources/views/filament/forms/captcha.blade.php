<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::input.wrapper>
        <x-filament::input
            type="text"
            wire:model="{{ $getStatePath() }}"
        />

        <x-slot name="suffix">
            <x-filament::loading-indicator
                wire:loading="auto"
                wire:target="mountAction('{{ $getStatePath() }}', ['refreshImage'])"
                class="ml-3 h-5 w-5"
            />
            <img wire:loading.remove
                 src="{{ $field->image }}"
                 wire:click="mountAction('{{ $getStatePath() }}', ['refreshImage'])"
                 style="width: 120px"
                 alt=""/>
        </x-slot>
    </x-filament::input.wrapper>
</x-dynamic-component>
