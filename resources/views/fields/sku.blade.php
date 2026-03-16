@php
    $statePath  = $getStatePath();
    $attrsPath  = $statePath . '.attrs';
    $skusPath   = $statePath . '.skus';
@endphp

<div
        x-data="{
        attrs: $wire.entangle('{{ $attrsPath }}').live,
        skus:  $wire.entangle('{{ $skusPath }}').live,

        uid() { return '_' + Math.random().toString(36).slice(2, 9); },

        addAttr() {
            this.attrs.push({ id: this.uid(), name: '', values: [] });
        },

        removeAttr(index) {
            this.attrs.splice(index, 1);
            this.rebuildSkus();
        },

        addValue(attrIndex) {
            this.attrs[attrIndex].values.push({ id: this.uid(), value: '' });
            this.rebuildSkus();
        },

        removeValue(attrIndex, valIndex) {
            this.attrs[attrIndex].values.splice(valIndex, 1);
            this.rebuildSkus();
        },

        rebuildSkus() {
            const validAttrs = this.attrs.filter(a => a.values && a.values.filter(v => v.value.trim()).length > 0);
            if (validAttrs.length === 0) { this.skus = []; return; }

            let combinations = [[]];
            for (const attr of validAttrs) {
                const validVals = attr.values.filter(v => v.value.trim());
                const next = [];
                for (const combo of combinations) {
                    for (const val of validVals) {
                        next.push([...combo, { attribute_id: attr.id, attribute_value_id: val.id }]);
                    }
                }
                combinations = next;
            }

            const oldSkuMap = {};
            for (const sku of this.skus) { oldSkuMap[sku.spec_key] = sku; }

            this.skus = combinations.map(spec => {
                const specKey = [...spec].sort((a, b) => String(a.attribute_id).localeCompare(String(b.attribute_id)))
                    .map(s => s.attribute_id + ':' + s.attribute_value_id).join('-');
                const old = oldSkuMap[specKey] || {};
                return {
                    id: old.id || null,
                    cover: old.cover || null,
                    price: old.price ?? '',
                    origin_price: old.origin_price ?? '',
                    stock: old.stock ?? '',
                    code: old.code ?? '',
                    spec: spec,
                    spec_key: specKey,
                };
            });
        },

        getSpecLabel(spec) {
            return spec.map(s => {
                const attr = this.attrs.find(a => a.id === s.attribute_id);
                const val  = attr ? attr.values.find(v => v.id === s.attribute_value_id) : null;
                return val ? val.value : '';
            }).filter(Boolean).join(' / ');
        },

        batchFill(field, value) {
            if (value === '' || value === null) return;
            this.skus = this.skus.map(sku => ({ ...sku, [field]: value }));
        },

        batchPrice: '',
        batchOriginPrice: '',
        batchStock: '',
    }"
>
    {{-- ========== 商品属性 ========== --}}
    <x-filament::fieldset label="商品属性">
        <div class="space-y-3">

            <template x-for="(attr, attrIndex) in attrs" :key="attr.id">
                <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
                    <div class="flex items-stretch">

                        {{-- 左栏：属性名称 --}}
                        <div class="w-48 flex-shrink-0 border-r border-gray-200 dark:border-white/10
                                    bg-gray-50 dark:bg-white/5 p-4 flex flex-col justify-between">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400
                                             uppercase tracking-wide block mb-2">属性名称</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                            type="text"
                                            x-model="attr.name"
                                            @change="rebuildSkus()"
                                            placeholder="例：颜色、尺码"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                            {{-- 删除属性 --}}
                            <button type="button"
                                    class="mt-4 flex items-center gap-1 text-xs text-gray-400
                                           hover:text-danger-500 transition w-fit"
                                    @click="removeAttr(attrIndex)">
                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5"/>
                                删除属性
                            </button>
                        </div>

                        {{-- 右栏：属性值 --}}
                        <div class="flex-1 p-4">
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400
                                         uppercase tracking-wide block mb-2">属性值</label>
                            <div class="flex flex-wrap gap-2 items-center">
                                <template x-for="(val, valIndex) in attr.values" :key="val.id">
                                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-white/10
                                                rounded-lg pl-2 pr-1 py-1">
                                        <x-filament::input
                                                type="text"
                                                x-model="val.value"
                                                @change="rebuildSkus()"
                                                placeholder="属性值"
                                                class="bg-transparent border-0 outline-none text-sm
                                                       w-20 focus:w-32 transition-all"
                                                style="box-shadow:none; padding: 0 4px;"
                                        />
                                        <button type="button"
                                                class="text-gray-400 hover:text-danger-500 transition
                                                       flex-shrink-0 rounded p-0.5
                                                       hover:bg-danger-50 dark:hover:bg-danger-500/10"
                                                @click="removeValue(attrIndex, valIndex)">
                                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5"/>
                                        </button>
                                    </div>
                                </template>

                                {{-- 添加属性值 --}}
                                <button type="button"
                                        class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-dashed
                                               border-gray-300 dark:border-white/20 text-xs text-gray-500
                                               hover:border-primary-400 hover:text-primary-600 transition"
                                        @click="addValue(attrIndex)">
                                    <x-filament::icon icon="heroicon-o-plus" class="h-3.5 w-3.5"/>
                                    添加值
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </template>

            {{-- 添加属性按钮 --}}
            <button type="button"
                    class="w-full rounded-xl border border-dashed border-gray-300 dark:border-white/20
                           py-3 flex justify-center items-center gap-2 text-sm text-gray-500
                           hover:border-primary-400 hover:text-primary-600 transition"
                    @click="addAttr()">
                <x-filament::icon icon="heroicon-o-plus-circle" class="h-5 w-5"/>
                添加属性
            </button>
        </div>
    </x-filament::fieldset>

    {{-- ========== SKU 列表 ========== --}}
    <x-filament::fieldset label="SKU 规格" class="mt-4">
        <div x-show="skus.length === 0" class="text-center text-gray-400 py-8 text-sm">
            请先添加商品属性和属性值，系统将自动生成 SKU 列表
        </div>

        <div x-show="skus.length > 0">
            <table style="width: 100%">
                <thead class="border-b border-gray-200 dark:border-white/10 text-left text-sm text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="p-2 font-medium">规格</th>
                    <th class="p-2 font-medium" style="width: 120px">图片</th>
                    <th class="p-2 font-medium" style="width: 140px">
                        <div class="flex flex-col gap-1">
                            <span>销售价（元）</span>
                            <x-filament::input.wrapper class="text-xs">
                                <x-filament::input
                                        type="number"
                                        x-model="batchPrice"
                                        @change="batchFill('price', batchPrice)"
                                        placeholder="批量填写"
                                        style="font-size: 11px"
                                />
                            </x-filament::input.wrapper>
                        </div>
                    </th>
                    <th class="p-2 font-medium" style="width: 140px">
                        <div class="flex flex-col gap-1">
                            <span>市场价（元）</span>
                            <x-filament::input.wrapper class="text-xs">
                                <x-filament::input
                                        type="number"
                                        x-model="batchOriginPrice"
                                        @change="batchFill('origin_price', batchOriginPrice)"
                                        placeholder="批量填写"
                                        style="font-size: 11px"
                                />
                            </x-filament::input.wrapper>
                        </div>
                    </th>
                    <th class="p-2 font-medium" style="width: 120px">
                        <div class="flex flex-col gap-1">
                            <span>库存</span>
                            <x-filament::input.wrapper class="text-xs">
                                <x-filament::input
                                        type="number"
                                        x-model="batchStock"
                                        @change="batchFill('stock', batchStock)"
                                        placeholder="批量填写"
                                        style="font-size: 11px"
                                />
                            </x-filament::input.wrapper>
                        </div>
                    </th>
                    <th class="p-2 font-medium" style="width: 180px">商品编码</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <template x-for="(sku, skuIndex) in skus" :key="sku.spec_key">
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                        {{-- 规格名称 --}}
                        <td class="p-2">
                            <span class="text-sm font-medium" x-text="getSpecLabel(sku.spec)"></span>
                        </td>
                        {{-- 图片 --}}
                        <td class="p-2">
                            <div class="flex items-center gap-2">
                                <template x-if="sku.cover">
                                    <img :src="sku.cover"
                                         class="h-10 w-10 rounded object-cover border border-gray-200 dark:border-white/10"
                                         alt="规格图"/>
                                </template>
                                <template x-if="!sku.cover">
                                    <div class="h-10 w-10 rounded border border-dashed border-gray-300 dark:border-white/20 flex items-center justify-center text-gray-300 text-xs">
                                        无
                                    </div>
                                </template>
                            </div>
                        </td>
                        {{-- 销售价 --}}
                        <td class="p-2">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                        type="number"
                                        x-model="sku.price"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                />
                            </x-filament::input.wrapper>
                        </td>
                        {{-- 市场价 --}}
                        <td class="p-2">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                        type="number"
                                        x-model="sku.origin_price"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                />
                            </x-filament::input.wrapper>
                        </td>
                        {{-- 库存 --}}
                        <td class="p-2">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                        type="number"
                                        x-model="sku.stock"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                />
                            </x-filament::input.wrapper>
                        </td>
                        {{-- 商品编码 --}}
                        <td class="p-2">
                            <x-filament::input.wrapper>
                                <x-filament::input
                                        type="text"
                                        x-model="sku.code"
                                        placeholder="条形码/SKU编号"
                                />
                            </x-filament::input.wrapper>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </x-filament::fieldset>
</div>
