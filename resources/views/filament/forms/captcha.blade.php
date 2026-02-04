<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ src: '{{ $field->image }}' }" class="flex items-center gap-3">
        <x-filament::input.wrapper class="shrink" style="width:100%">
            <x-filament::input type="text" wire:model="{{ $getStatePath() }}" class="w-full" />
        </x-filament::input.wrapper>

        <img x-bind:src="src" x-on:click="src = src.split('?')[0] + '?' + Date.now()"
            style="border-radius: 6px;margin-left: 8px;cursor: pointer" alt="captcha" src="" />
    </div>
</x-dynamic-component>
