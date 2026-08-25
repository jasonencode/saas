<?php

namespace App\Rules\Mall;

use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Region;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 通过中文名称验证地区
 *
 * 校验规则：地区存在、层级匹配、父子归属正确。
 *
 * 用法示例：
 * ```
 * 'province' => [new RegionNameRule(RegionLevel::Province)],
 * ```
 */
class RegionNameRule implements DataAwareRule, ValidationRule
{
    /**
     * 表单数据
     */
    public array $data = [];

    /**
     * 创建地区验证规则
     *
     * @param  RegionLevel  $level  地区层级
     */
    public function __construct(protected RegionLevel $level = RegionLevel::Province) {}

    /**
     * 验证地区名称
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  地区名称
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('所在地区必须填写');
            return;
        }

        $region = Region::where('name', $value)->first();

        if (!$region) {
            $fail('您选择的地域不存在');
            return;
        }

        if ($this->level === RegionLevel::Province && $region->level !== RegionLevel::Province) {
            $fail('您选择的不是一个省份');
            return;
        }

        if ($this->level === RegionLevel::City) {
            if ($region->level !== RegionLevel::City) {
                $fail('您选择的不是一个城市');
                return;
            }

            $provinceName = $this->data['province'] ?? null;
            if ($provinceName) {
                $province = Region::where('name', $provinceName)->where('level', RegionLevel::Province)->first();
                if ($province && $region->parent_id !== $province->id) {
                    $fail('选择的城市，不属于这个省');
                }
            }
        }

        if ($this->level === RegionLevel::District) {
            if ($region->level !== RegionLevel::District) {
                $fail('您选择的不是一个区县');
                return;
            }

            $cityName = $this->data['city'] ?? null;
            if ($cityName) {
                $city = Region::where('name', $cityName)->where('level', RegionLevel::City)->first();
                if ($city && $region->parent_id !== $city->id) {
                    $fail('选择的区县，不属于这个市');
                }
            }
        }
    }

    /**
     * 设置表单数据
     *
     * @param  array  $data  表单数据
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
