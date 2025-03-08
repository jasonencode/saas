<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallPanel extends Command
{
    protected $signature = 'install';

    protected $description = '初始化系统数据库';

    public function handle(): int
    {
        $this->line('初始化安装');

        if (!file_exists('.env')) {
            $this->warn('配置文件不存在，初始化配置文件');
            copy('.env.example', '.env');
        }

        Artisan::call('key:generate');

        $this->confirm('确认数据库连接配置已完成？');

        Artisan::call('migrate', [
            '--path' => '/database/migrations',
        ]);
        $this->info('数据库安装完成');

        Artisan::call('db:seed');
        $this->info('数据库填充完成');

        return self::SUCCESS;
    }
}