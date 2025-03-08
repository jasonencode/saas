<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemTableSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = [
            ['id' => 1, 'username' => '系统'],
            ['id' => 2, 'username' => '队列'],
            ['id' => 3, 'username' => '计划任务'],
        ];
        DB::table('systems')->insert($seeds);
    }
}