<div
    x-data="{ open: false }"
    @click.outside="open = false"
    class="relative"
>
    {{-- 触发按钮 --}}
    <x-filament::icon-button
        :icon="$icon"
        color="gray"
        :label="$label"
        :tooltip="$tooltip ?: $label"
        x-on:click="open = !open"
    />

    {{-- 下拉内容 --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        style="min-width: {{ $width }}"
        class="fi-dropdown-panel absolute right-0 z-50 mt-2 rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    >
        {{-- 下拉头部 --}}
        <div class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $header }}</h3>
        </div>

        {{-- 下拉内容区域：在此自定义内容 --}}
        <div class="max-h-64 overflow-y-auto px-4 py-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $empty }}</p>
        </div>
    </div>
</div>
