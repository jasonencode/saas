<?php

namespace App\Contracts\Attributes;

use Attribute;

/**
 * 命令名称特性
 *
 * 标注在 BaseCommand 子类上，作为调度日志等场景展示的中文名称，
 * 替代子类逐个实现 getCommandLabel() 方法。
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CommandLabel
{
    /**
     * 初始化命令名称特性
     *
     * @param  string  $label  命令名称
     */
    public function __construct(private string $label) {}

    /**
     * 获取命令名称
     *
     * @return string 命令名称
     */
    public function getLabel(): string
    {
        return $this->label;
    }
}
