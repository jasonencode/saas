<x-dynamic-component
        :component="$getFieldWrapperView()"
        :field="$field"
>
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        captchaUrl: '{{ route('captcha.generate') }}',
        refreshCaptcha() {
            this.captchaUrl = '{{ route('captcha.generate') }}?' + Date.now()
        }
    }">
        <div class="flex items-center space-x-2">
            <div class="flex-1">
                <x-filament::input
                        :attributes="
                        $attributes
                            ->merge([
                                'autocomplete' => 'off',
                                'type' => 'text',
                            ], escape: false)
                            ->merge($getExtraInputAttributes(), escape: false)
                    "
                        x-model="state"
                />
            </div>

            <div class="flex-none">
                <img
                        :src="captchaUrl"
                        class="h-10 cursor-pointer"
                        @click="refreshCaptcha"
                        alt="验证码"
                />
            </div>
        </div>
    </div>
</x-dynamic-component>
