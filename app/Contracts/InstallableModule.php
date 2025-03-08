<?php

namespace App\Contracts;

use App\Extensions\Module\InstallOption;

interface InstallableModule
{
    /**
     * 安装时调用
     *
     * @param  InstallOption|null  $options
     * @return void
     */
    public function install(?InstallOption $options = null): void;

    /**
     * 卸载调用
     *
     * @param  InstallOption|null  $options
     * @return void
     */
    public function uninstall(?InstallOption $options = null): void;
}