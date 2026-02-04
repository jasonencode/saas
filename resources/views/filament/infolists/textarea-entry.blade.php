<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <x-filament::input.wrapper
            :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class(['fi-fo-textarea overflow-hidden'])
        ">
        <textarea x-intersect.once="resize()"
                  x-on:resize.window="resize()"
                  rows="{{ $getRows() }}"
                  readonly
                  disabled
                  class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 dark:text-white sm:text-sm sm:leading-6'"
        >{{ $getState() }}</textarea>
    </x-filament::input.wrapper>
</x-dynamic-component>
