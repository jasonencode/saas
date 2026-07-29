<div>
    @if ($exists)
        <div x-data="{ open: false }" class="relative">
            <button
                x-on:click="open = true"
                type="button"
                class="fi-topbar-item flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-500/10 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                title="帮助文档"
            >
                <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-5 w-5" />
            </button>

            <template x-teleport="body">
                <div
                    x-show="open"
                    x-on:keydown.escape.window="open = false"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>

                    <div
                        class="relative z-10 w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">帮助文档</h3>
                            <button
                                x-on:click="open = false"
                                type="button"
                                class="rounded-lg p-1 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="fi-markdown-content" style="max-height: 60vh; overflow-y: auto; font-size: 0.875rem; line-height: 1.7; color: var(--gray-700);">
                            <style>
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
                                .dark .fi-markdown-content { color: var(--gray-300); }
                                .dark .fi-markdown-content th { background: var(--gray-700); border-color: var(--gray-600); }
                                .dark .fi-markdown-content td { border-color: var(--gray-600); }
                                .dark .fi-markdown-content tr:nth-child(even) { background: var(--gray-700); }
                                .dark .fi-markdown-content code { background: var(--gray-700); }
                                .dark .fi-markdown-content blockquote { background: var(--primary-900); border-color: var(--primary-500); }
                                .dark .fi-markdown-content a { color: var(--primary-400); }
                            </style>
                            {!! $html !!}
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
