<?php

namespace Tests\Unit\Console\Commands\Maintenance;

use PHPUnit\Framework\TestCase;

class CleanUnusedFilesCommandTest extends TestCase
{
    /**
     * 外部 URL 应被过滤，不参与匹配
     */
    public function test_looks_external_filters_urls_and_static_assets(): void
    {
        $command = $this->getCommand();

        $this->assertTrue($command->looksExternal(''));
        $this->assertTrue($command->looksExternal('https://example.com/a.jpg'));
        $this->assertTrue($command->looksExternal('http://example.com/a.jpg'));
        $this->assertTrue($command->looksExternal('//example.com/a.jpg'));
        $this->assertTrue($command->looksExternal('images/avatar.jpg'));
        $this->assertTrue($command->looksExternal('storage/xxx.png'));
        $this->assertTrue($command->looksExternal('1/2026/08/03/a.jpg') === false);
    }

    /**
     * 字符串与 JSON 数组字段均能正确解析为引用路径
     */
    public function test_collect_field_paths_parses_strings_and_arrays(): void
    {
        $command = $this->getCommand();

        $command->collectFieldPaths('1/2026/08/03/a.jpg');
        $command->collectFieldPaths(['1/2026/08/03/b.jpg', '1/2026/08/03/c.jpg']);
        $command->collectFieldPaths(null);
        $command->collectFieldPaths('');
        $command->collectFieldPaths('https://cdn.example.com/x.png');

        $this->assertArrayHasKey('1/2026/08/03/a.jpg', $command->getReferencedPaths());
        $this->assertArrayHasKey('1/2026/08/03/b.jpg', $command->getReferencedPaths());
        $this->assertArrayHasKey('1/2026/08/03/c.jpg', $command->getReferencedPaths());
        $this->assertArrayNotHasKey('https://cdn.example.com/x.png', $command->getReferencedPaths());
    }

    /**
     * livewire-tmp 等排除目录中的文件不应被清理
     */
    public function test_is_excluded_directory_skips_livewire_tmp(): void
    {
        $command = $this->getCommand();

        $this->assertTrue($command->isExcludedDirectory('livewire-tmp'));
        $this->assertTrue($command->isExcludedDirectory('livewire-tmp/abc123'));
        $this->assertTrue($command->isExcludedDirectory('livewire-tmp/nested/file.png'));
        $this->assertTrue($command->isExcludedDirectory('1/2026/08/03/a.jpg') === false);
        $this->assertTrue($command->isExcludedDirectory('livewire-tmp-backup/a.jpg') === false);
    }

    /**
     * 带前导斜杠的路径应标准化为相对路径
     */
    public function test_add_referenced_path_normalizes_leading_slash(): void
    {
        $command = $this->getCommand();

        $command->collectFieldPaths('/1/2026/08/03/a.jpg');

        $this->assertArrayHasKey('1/2026/08/03/a.jpg', $command->getReferencedPaths());
        $this->assertArrayNotHasKey('/1/2026/08/03/a.jpg', $command->getReferencedPaths());
    }

    /**
     * .gitignore 等隐藏文件不应被清理
     */
    public function test_is_hidden_file_skips_dotfiles(): void
    {
        $command = $this->getCommand();

        $this->assertTrue($command->isHiddenFile('.gitignore'));
        $this->assertTrue($command->isHiddenFile('.gitattributes'));
        $this->assertTrue($command->isHiddenFile('.DS_Store'));
        $this->assertTrue($command->isHiddenFile('nested/.gitignore'));
        $this->assertTrue($command->isHiddenFile('1/2026/08/03/a.jpg') === false);
    }

    /**
     * 富文本 HTML 中的图片路径应被提取为引用
     */
    public function test_collect_rich_text_paths_extracts_html_images(): void
    {
        $command = $this->getCommand();

        $command->collectRichTextPaths(
            '<p>Hello</p><img src="1/2026/08/03/a.jpg" alt="x">'
            .'<img src="https://cdn.example.com/remote.png">'
            .'<video poster="/2/2026/08/04/b.jpg"></video>'
            .'<iframe src="3/2026/08/05/c.jpg"></iframe>'
            .'![md](4/2026/08/06/d.jpg)'
        );

        $paths = $command->getReferencedPaths();

        $this->assertArrayHasKey('1/2026/08/03/a.jpg', $paths);
        $this->assertArrayHasKey('2/2026/08/04/b.jpg', $paths);
        $this->assertArrayHasKey('3/2026/08/05/c.jpg', $paths);
        $this->assertArrayHasKey('4/2026/08/06/d.jpg', $paths);
        $this->assertArrayNotHasKey('https://cdn.example.com/remote.png', $paths);
    }

    /**
     * 富文本中的 HTML 实体应被解码后再匹配
     */
    public function test_collect_rich_text_paths_decodes_html_entities(): void
    {
        $command = $this->getCommand();

        $command->collectRichTextPaths('<img src="1/2026/08/03/&amp;.jpg">');

        $this->assertArrayHasKey('1/2026/08/03/&.jpg', $command->getReferencedPaths());
    }

    /**
     * 字节数格式化
     */
    public function test_human_bytes(): void
    {
        $command = $this->getCommand();

        $this->assertSame('0 B', $command->humanBytes(0));
        $this->assertSame('1 KB', $command->humanBytes(1024));
        $this->assertSame('1.5 MB', $command->humanBytes(1572864));
        $this->assertSame('1 GB', $command->humanBytes(1073741824));
    }

    /**
     * 创建暴露受保护方法的命令实例
     */
    private function getCommand(): CleanUnusedFilesCommandTestable
    {
        return new CleanUnusedFilesCommandTestable;
    }
}
