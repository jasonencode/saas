<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @php
        $nodes = $entry->getNodes();
    @endphp

    <div @class([
        'fi-in-relation-tree rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5',
    ])>
        @if ($nodes['parent'])
            <div class="mb-3">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">直接上级</div>
                <div class="ml-0 flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-gray-50 dark:hover:bg-white/5">
                    <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $nodes['parent']['id'] }}</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $nodes['parent']['label'] }}</span>
                </div>
            </div>
        @endif

        <div>
            <div class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">当前用户</div>
            <div class="flex items-center gap-2 rounded-lg bg-primary-50 px-2 py-1 ring-1 ring-primary-200 dark:bg-primary-950 dark:ring-primary-800">
                <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $nodes['root']['id'] }}</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $nodes['root']['label'] }}</span>
            </div>
        </div>

        @forelse ($nodes['children'] as $child)
            <div class="mt-2">
                @include('filament.infolists.relation-tree-node', ['node' => $child])
            </div>
        @empty
            <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">暂无下级</div>
        @endforelse
    </div>
</x-dynamic-component>