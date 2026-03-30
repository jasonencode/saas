<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemTableSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = [
            ['id' => 1, 'name' => '系统', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '队列', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '计划任务', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('systems')->insert($seeds);

        if (DB::getDriverName() === 'pgsql') {
            $maxId = DB::table('systems')->max('id');
            DB::statement("SELECT setval(pg_get_serial_sequence('systems', 'id'), coalesce($maxId, 1))");
        }
    }
}
