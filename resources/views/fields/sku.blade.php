@php
    $statePath = $getStatePath();
    $attrsPath  = $statePath . '.attrs';
    $skusPath   = $statePath . '.skus';
@endphp

<div
    class="space-y-6"
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
    {{-- ======== Attribute Editor ======== --}}
    <x-filament::fieldset label="商品属性">
        <div class="space-y-3">
            <template x-for="(attr, attrIndex) in attrs" :key="attr.id">
                <div class="bg-white dark:bg-gray-900 overflow-hidden">
                    <div class="flex items-stretch">
                        {{-- Left: attribute name --}}
                        <div class="w-48 shrink-0 border-e border-gray-200 dark:border-white/10 p-4 bg-gray-50 dark:bg-white/2.5 flex flex-col justify-between">
                            <div class="space-y-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <x-filament::icon icon="heroicon-o-tag" class="h-3 w-3"/>
                                    属性名称
                                </span>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                            type="text"
                                            x-model="attr.name"
                                            @change="rebuildSkus()"
                                            placeholder="例：颜色、尺码"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                            <button type="button"
                                    class="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400 hover:text-red-500 transition-colors"
                                    @click="removeAttr(attrIndex)">
                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5"/>
                                删除
                            </button>
                        </div>

                        {{-- Right: attribute values --}}
                        <div class="flex-1 p-4">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1 mb-3">属性值</span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(val, valIndex) in attr.values" :key="val.id">
                                    <div class="inline-flex items-center gap-1 bg-gray-50 dark:bg-white/5 rounded-lg ps-3 pe-1.5 py-1 border border-gray-200 dark:border-white/10">
                                        <input
                                                type="text"
                                                x-model="val.value"
                                                @change="rebuildSkus()"
                                                placeholder="属性值"
                                                class="bg-transparent border-0 outline-none text-sm w-20 focus:w-32 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                                                style="box-shadow:none;padding:0 4px;"
                                        />
                                        <button type="button"
                                                class="text-gray-400 dark:text-gray-500 hover:text-red-500 transition-colors p-0.5 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20"
                                                @click="removeValue(attrIndex, valIndex)">
                                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </template>
                                <button type="button"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border-2 border-dashed border-gray-300 dark:border-white/20 text-xs font-medium text-gray-500 dark:text-gray-400 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 transition-all"
                                        @click="addValue(attrIndex)">
                                    <x-filament::icon icon="heroicon-o-plus" class="h-3.5 w-3.5"/>
                                    添加
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <button type="button"
                    class="w-full rounded-xl border-2 border-dashed border-gray-300 dark:border-white/20 py-4 flex items-center justify-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 transition-all"
                    @click="addAttr()">
                <x-filament::icon icon="heroicon-o-plus-circle" class="h-5 w-5"/>
                添加属性
            </button>
        </div>
    </x-filament::fieldset>

    {{-- ======== SKU List ======== --}}
    <x-filament::fieldset label="SKU 规格">
        {{-- Empty state --}}
        <div x-show="skus.length === 0"
             class="flex flex-col items-center justify-center py-12 text-sm rounded-xl border-2 border-dashed text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10">
            <x-filament::icon icon="heroicon-o-inbox" class="h-10 w-10 mb-3 text-gray-300 dark:text-gray-600"/>
            请先添加商品属性和属性值，系统将自动生成 SKU 列表
        </div>

        {{-- Table --}}
        <div x-show="skus.length > 0" class="overflow-x-auto">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">规格</th>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap w-[60px]">图片</th>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[130px]">
                            <div class="flex flex-col gap-1">
                                <span>销售价（元）</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                            type="number"
                                            x-model="batchPrice"
                                            @change="batchFill('price', batchPrice)"
                                            placeholder="批量"
                                            class="text-xs"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                        </th>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[130px]">
                            <div class="flex flex-col gap-1">
                                <span>市场价（元）</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                            type="number"
                                            x-model="batchOriginPrice"
                                            @change="batchFill('origin_price', batchOriginPrice)"
                                            placeholder="批量"
                                            class="text-xs"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                        </th>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap w-[100px]">
                            <div class="flex flex-col gap-1">
                                <span>库存</span>
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                            type="number"
                                            x-model="batchStock"
                                            @change="batchFill('stock', batchStock)"
                                            placeholder="批量"
                                            class="text-xs"
                                    />
                                </x-filament::input.wrapper>
                            </div>
                        </th>
                        <th class="px-3 py-2.5 text-start text-xs font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[130px]">商品编码</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10 bg-white dark:bg-gray-900">
                    <template x-for="(sku, skuIndex) in skus" :key="sku.spec_key">
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-3 py-2 align-middle">
                                <span class="text-sm font-medium text-gray-950 dark:text-white" x-text="getSpecLabel(sku.spec)"></span>
                            </td>
                            <td class="px-3 py-2 align-middle">
                                <template x-if="sku.cover">
                                    <img :src="sku.cover" class="size-8 rounded object-cover border border-gray-200 dark:border-white/10" alt="规格图"/>
                                </template>
                                <template x-if="!sku.cover">
                                    <div class="size-8 rounded border-2 border-dashed border-gray-300 dark:border-white/20 flex items-center justify-center text-gray-300 dark:text-gray-600">
                                        <x-filament::icon icon="heroicon-o-photo" class="h-4 w-4"/>
                                    </div>
                                </template>
                            </td>
                            <td class="px-3 py-1.5 align-middle">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="number" x-model="sku.price" min="0" step="0.01" placeholder="0.00"/>
                                </x-filament::input.wrapper>
                            </td>
                            <td class="px-3 py-1.5 align-middle">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="number" x-model="sku.origin_price" min="0" step="0.01" placeholder="0.00"/>
                                </x-filament::input.wrapper>
                            </td>
                            <td class="px-3 py-1.5 align-middle">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="number" x-model="sku.stock" min="0" step="1" placeholder="0"/>
                                </x-filament::input.wrapper>
                            </td>
                            <td class="px-3 py-1.5 align-middle">
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" x-model="sku.code" placeholder="条形码/SKU编号"/>
                                </x-filament::input.wrapper>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::fieldset>
</div>
