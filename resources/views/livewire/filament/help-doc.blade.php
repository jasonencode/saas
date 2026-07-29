<div>
    @if ($exists)
        <button
                x-on:click="$dispatch('open-modal', { id: 'help-doc' })"
                type="button"
                class="fi-topbar-item-btn group h-9 w-9 px-0"
                title="帮助文档"
        >
            <x-filament::icon
                    icon="heroicon-o-question-mark-circle"
                    class="h-5 w-5 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400"
            />
        </button>

        <x-filament::modal
                id="help-doc"
                width="4xl"
        >
            <x-slot name="heading">
                {{ __('帮助文档') }}
            </x-slot>

            <div class="fi-markdown-content">
                <style>
                    .fi-markdown-content { font-size: 0.875rem; line-height: 1.7; color: var(--gray-700); }
                    .fi-markdown-content h1 { font-size: 1.5rem; font-weight: 700; margin: 1.5rem 0 0.75rem; color: var(--gray-950); }
                    .fi-markdown-content h2 { font-size: 1.25rem; font-weight: 600; margin: 1.25rem 0 0.5rem; color: var(--gray-950); }
                    .fi-markdown-content h3 { font-size: 1.125rem; font-weight: 600; margin: 1rem 0 0.5rem; color: var(--gray-950); }
                    .fi-markdown-content p { margin: 0.5rem 0; }
                    .fi-markdown-content ul, .fi-markdown-content ol { margin: 0.5rem 0; padding-left: 1.5rem; }
                    .fi-markdown-content li { margin: 0.25rem 0; }
                    .fi-markdown-content ul { list-style-type: disc; }
                    .fi-markdown-content ol { list-style-type: decimal; }
                    .fi-markdown-content table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.8125rem; }
                    .fi-markdown-content th { background: var(--gray-50); font-weight: 600; text-align: left; padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); }
                    .fi-markdown-content td { padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); }
                    .fi-markdown-content tr:nth-child(even) { background: var(--gray-50); }
                    .fi-markdown-content code { background: var(--gray-100); padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.8125rem; }
                    .fi-markdown-content pre { background: var(--gray-950); color: var(--gray-100); padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin: 1rem 0; }
                    .fi-markdown-content pre code { background: transparent; padding: 0; color: inherit; }
                    .fi-markdown-content blockquote { border-left: 4px solid var(--primary-500); padding: 0.5rem 1rem; margin: 1rem 0; background: var(--primary-50); border-radius: 0 0.25rem 0.25rem 0; }
                    .fi-markdown-content hr { border: none; border-top: 1px solid var(--gray-200); margin: 1.5rem 0; }
                    .fi-markdown-content a { color: var(--primary-600); text-decoration: underline; }
                    .fi-markdown-content a:hover { color: var(--primary-700); }
                    .fi-markdown-content strong { font-weight: 600; color: var(--gray-950); }
                    .dark .fi-markdown-content h1, .dark .fi-markdown-content h2, .dark .fi-markdown-content h3, .dark .fi-markdown-content strong { color: var(--gray-100); }
                    .dark .fi-markdown-content { color: var(--gray-200); }
                    .dark .fi-markdown-content th { background: var(--gray-700); border-color: var(--gray-600); }
                    .dark .fi-markdown-content td { border-color: var(--gray-600); }
                    .dark .fi-markdown-content tr:nth-child(even) { background: var(--gray-700); }
                    .dark .fi-markdown-content code { background: var(--gray-700); }
                    .dark .fi-markdown-content blockquote { background: var(--primary-900); border-color: var(--primary-500); }
                    .dark .fi-markdown-content a { color: var(--primary-400); }
                </style>
                {!! $html !!}
            </div>
        </x-filament::modal>
    @endif
</div>
