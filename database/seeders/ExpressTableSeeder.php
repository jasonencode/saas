<?php

namespace Database\Seeders;

use App\Models\Express;
use Illuminate\Database\Seeder;

class ExpressTableSeeder extends Seeder
{
    public function run(): void
    {
        $seeds = [
            ['name' => '圆通速递', 'code' => 'yuantong', 'status' => true],
            ['name' => '韵达快递', 'code' => 'yunda', 'status' => true],
            ['name' => '中通快递', 'code' => 'zhongtong', 'status' => true],
            ['name' => '申通快递', 'code' => 'shentong', 'status' => true],
            ['name' => '极兔速递', 'code' => 'jtexpress', 'status' => true],
            ['name' => '顺丰速运', 'code' => 'shunfeng', 'status' => true],
            ['name' => '邮政快递包裹', 'code' => 'youzhengguonei', 'status' => true],
            ['name' => 'EMS', 'code' => 'ems', 'status' => true],
            ['name' => '京东物流', 'code' => 'jd', 'status' => true],
            ['name' => '邮政标准快递', 'code' => 'youzhengbk', 'status' => true],
            ['name' => '德邦快递', 'code' => 'debangkuaidi', 'status' => true],
            ['name' => '德邦物流', 'code' => 'debangwuliu', 'status' => true],
        ];

        foreach ($seeds as $seed) {
            Express::create($seed);
        }
    }
}
