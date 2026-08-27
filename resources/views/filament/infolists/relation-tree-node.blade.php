@php
    $user = $node['user'];
    $label = $user->username.($user->profile?->nickname ? '（'.$user->profile?->nickname.'）' : '');
@endphp

<div class="ml-4 border-l border-gray-200 pl-3 dark:border-white/10">
    <div class="flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-gray-50 dark:hover:bg-white/5">
        <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $user->getKey() }}</span>
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
    </div>

    @foreach ($node['children'] ?? [] as $child)
        @include('filament.infolists.relation-tree-node', ['node' => $child])
    @endforeach
</div>