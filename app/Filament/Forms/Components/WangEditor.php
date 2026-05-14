<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class WangEditor extends Field
{
    protected string $view = 'filament.forms.wang-editor';

    protected int $minHeight = 300;

    protected array $editorConfig = [];

    protected ?string $cdnVersion = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('');
    }

    /**
     * 设置编辑器最小高度（px）
     */
    public function minHeight(int $height): static
    {
        $this->minHeight = $height;

        return $this;
    }

    public function getMinHeight(): int
    {
        return $this->minHeight;
    }

    /**
     * 传递给 createEditor() 的额外配置
     */
    public function editorConfig(array $config): static
    {
        $this->editorConfig = $config;

        return $this;
    }

    public function getEditorConfig(): array
    {
        return $this->editorConfig;
    }

    /**
     * 锁定 CDN 版本号，不设置则使用 @latest
     */
    public function cdnVersion(?string $version): static
    {
        $this->cdnVersion = $version;

        return $this;
    }

    public function getCdnVersion(): string
    {
        return $this->cdnVersion ?? 'latest';
    }

    /**
     * 获取 CDN CSS URL
     */
    public function getCssUrl(): string
    {
        $version = $this->getCdnVersion();

        return "https://cdn.jsdelivr.net/npm/@wangeditor/editor@{$version}/dist/css/style.css";
    }

    /**
     * 获取 CDN ESM URL
     */
    public function getEsmUrl(): string
    {
        $version = $this->getCdnVersion();

        return "https://cdn.jsdelivr.net/npm/@wangeditor/editor@{$version}/dist/index.mjs";
    }
}
