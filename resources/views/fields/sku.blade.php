<x-filament::fieldset label="商品属性">
    <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3  xl:grid-cols-4 gap-4">

        <div class="relative rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <x-filament-forms::field-wrapper
                    :hasInlineLabel="false"
                    label="属性名称"
            >
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="颜色"
                    />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper
                    :hasInlineLabel="false"
                    label="属性值"
                    class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10"
            >
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="红色"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="黄色"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="蓝色"
                    />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
            <div class="flex justify-center mt-4">
                <x-filament::button size="xs">
                    添加属性值
                </x-filament::button>
            </div>
        </div>

        <div class="relative rounded-xl border border-gray-200 dark:border-white/10 p-4">
            <x-filament-forms::field-wrapper
                    :hasInlineLabel="false"
                    label="属性名称"
            >
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="尺码"
                    />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>

            <x-filament-forms::field-wrapper
                    :hasInlineLabel="false"
                    label="属性值"
                    class="mt-4 pt-2 border-t border-gray-200 dark:border-white/10"
            >
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="S"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="M"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper
                        suffixIcon="heroicon-o-x-mark"
                        suffixIconColor="primary"
                >
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                            value="L"
                    />
                </x-filament::input.wrapper>
            </x-filament-forms::field-wrapper>
            <div class="flex justify-center mt-4">
                <x-filament::button size="xs">
                    添加属性值
                </x-filament::button>
            </div>
        </div>

        <div class="relative rounded-xl border border-gray-200 dark:border-white/10 p-4 flex justify-center items-center">
            <x-filament::button>
                添加属性
            </x-filament::button>
        </div>
    </div>
</x-filament::fieldset>

<x-filament::fieldset label="SKU">
    <table class="" style="width: 100%">
        <thead class="border-b border-gray-200 dark:border-white/10">
        <tr>
            <th class="p-2" style="">规格</th>
            <th class="p-2" style="">图片</th>
            <th class="p-2" style="width: 8%">销售价（元）</th>
            <th class="p-2" style="width: 8%">市场价（元）</th>
            <th class="p-2" style="width: 8%">库存</th>
            <th class="p-2" style="width: 15%">商品编码</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="p-2">红色-S</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-M</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        <tr>
            <td class="p-2">红色-L</td>
            <td class="p-2"></td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
            <td class="p-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                            type="text"
                            wire:model="attributes[]"
                    />
                </x-filament::input.wrapper>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="flex justify-end gap-4 p-2 items-center">
        <x-filament::button>
            销售价
        </x-filament::button>
        <x-filament::button>
            市场价
        </x-filament::button>
        <x-filament::button>
            库存
        </x-filament::button>
    </div>
</x-filament::fieldset>
