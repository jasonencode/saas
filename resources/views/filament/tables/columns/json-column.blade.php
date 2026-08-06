<div {{ $attributes->class(['fi-ta-text fi-ta-text-item fi-size-sm overflow-hidden']) }}>
    @if(filled($getState()))
        <div class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 dark:text-white sm:text-sm sm:leading-6 whitespace-pre-wrap">{{ $formatState($getState()) }}</div>
    @else
        <div class="block h-full w-full bg-transparent px-3 py-1.5 text-base text-gray-400 dark:text-gray-500 sm:text-sm sm:leading-6">
            {{ $getPlaceholder() ?? '-' }}
        </div>
    @endif
</div>
