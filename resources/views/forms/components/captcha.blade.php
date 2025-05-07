<x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field"
>
    <x-filament::input.wrapper>
        <div class="flex overflow-hidden ">
            <x-filament::input
                    type="text"
                    wire:model="{{ $getStatePath() }}"
            />
            <div class="inline-flex space-x-2">
                <x-filament::loading-indicator
                        wire:loading
                        wire:target="mountFormComponentAction('{{ $getStatePath() }}', 'refreshImage')"
                        class="ml-3 h-5 w-5"
                />
            </div>
            <img wire:loading.remove wire:model="image"
                 wire:click="mountFormComponentAction('{{ $getStatePath() }}', 'refreshImage');"
                 class="rounded cursor-pointer border-solid border-2" src="{{ $field->image }}"
                 style="width: 120px"/>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>