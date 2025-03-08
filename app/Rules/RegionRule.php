<?php

namespace App\Rules;

use App\Enums\RegionLevel;
use App\Models\Region;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class RegionRule implements ValidationRule, DataAwareRule
{
    protected array $data;

    public function __construct(protected RegionLevel $level = RegionLevel::Province)
    {
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $region = Region::find($value);

        if (!$region) {
            $fail('您选择的地域不存在');

            return;
        }

        if ($this->level == RegionLevel::Province && $region->level != RegionLevel::Province) {
            $fail('您选择的不是一个省份');

            return;
        }

        if ($this->level == RegionLevel::City) {
            if ($region->level != RegionLevel::City) {
                $fail('您选择的不是一个城市');

                return;
            }
            if ($region->parent_id != $this->data['province_id']) {
                $fail('选择的城市，不属于这个省');

                return;
            }
        }

        if ($this->level == RegionLevel::District) {
            if ($region->level != RegionLevel::District) {
                $fail('您选择的不是一个区县');

                return;
            }
            if ($region->parent_id != $this->data['city_id']) {
                $fail('选择的区县，不属于这个市');
            }
        }
    }
}