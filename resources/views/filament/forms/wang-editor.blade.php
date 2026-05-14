@php
    $statePath = $getStatePath();
    $minHeight = $getMinHeight();
    $editorConfig = $getEditorConfig();
    $esmUrl = $getEsmUrl();
    $cssUrl = $getCssUrl();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- WangEditor 样式 --}}
    <link rel="stylesheet" href="{{ $cssUrl }}">

    <div
        x-data="wangEditorField({
            statePath: @js($statePath),
            esmUrl: @js($esmUrl),
            minHeight: @js($minHeight),
            editorConfig: @js($editorConfig),
        })"
        x-init="init"
        wire:ignore
    >
        {{-- 工具栏 --}}
        <div
            x-ref="toolbar"
            class="border border-b-0 border-gray-300 dark:border-gray-600 rounded-t-lg bg-white dark:bg-gray-900"
        ></div>

        {{-- 编辑区域 --}}
        <div
            x-ref="editor"
            class="border border-gray-300 dark:border-gray-600 rounded-b-lg"
            :style="`min-height: ${minHeight}px`"
        ></div>
    </div>

    {{-- wangEditor Alpine 组件定义 --}}
    @once
        <script type="module">
            document.addEventListener('alpine:init', () => {
                Alpine.data('wangEditorField', (config) => ({
                    editor: null,
                    content: null,
                    statePath: config.statePath,
                    esmUrl: config.esmUrl,
                    minHeight: config.minHeight,
                    editorConfig: config.editorConfig,

                    async init() {
                        // 1) 从 Livewire 获取初始内容（等 DOM 就绪）
                        this.content = await this.$wire.get(this.statePath);

                        // 2) 动态加载 wangEditor ESM
                        const { createEditor, createToolbar } = await import(this.esmUrl);

                        // 3) 创建工具栏
                        createToolbar({
                            selector: this.$refs.toolbar,
                            config: {},
                        });

                        // 4) 创建编辑器
                        this.editor = createEditor({
                            selector: this.$refs.editor,
                            config: {
                                placeholder: '请输入内容...',
                                ...this.editorConfig,
                            },
                        });

                        // 5) 设置初始内容
                        this.editor.setHtml(this.content || '');

                        // 6) 内容变化时同步到 Livewire
                        this.editor.onChange = () => {
                            const html = this.editor.getHtml();
                            if (html !== this.content) {
                                this.content = html;
                                this.$wire.set(this.statePath, html);
                            }
                        };
                    },
                }));
            });
        </script>
    @endonce
</x-dynamic-component>
