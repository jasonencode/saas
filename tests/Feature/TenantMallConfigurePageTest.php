<?php

namespace Tests\Feature;

use App\Filament\Tenant\Clusters\Mall\Pages\Configure;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantMallConfigurePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_hydrates_cover_state_for_livewire_entanglement(): void
    {
        $this->authenticateTenantPanel();

        $component = Livewire::test(Configure::class);

        $this->assertArrayHasKey('cover', $component->get('data'));
        $this->assertSame([], $component->get('data')['cover']);
    }

    public function test_it_describes_store_configuration_fields(): void
    {
        $this->authenticateTenantPanel();

        $formComponents = Livewire::test(Configure::class)
            ->instance()
            ->form;

        $this->assertFieldHelperText($formComponents, 'data.store_name', '展示在店铺前台、订单和售后等位置。');
        $this->assertFieldHelperText($formComponents, 'data.cover', '建议上传清晰的正方形图片，用作店铺LOGO。');
        $this->assertFieldHelperText($formComponents, 'data.store_description', '用于简要介绍店铺，最多 255 个字符。');
        $this->assertFieldHelperText($formComponents, 'data.default_express_id', '创建发货信息时默认选中的快递公司，可在发货时修改。');
        $this->assertFieldHelperText($formComponents, 'data.auto_complete_days', '订单发货后超过该天数未确认收货时，系统将自动完成订单。');
        $this->assertFieldHelperText($formComponents, 'data.order_expired_minutes', '买家下单后超过该时间未支付时，系统将自动取消订单。');
        $this->assertSame('分钟', $formComponents->getComponentByStatePath(
            'data.order_expired_minutes',
            withHidden: true,
            withAbsoluteStatePath: true,
        )->getSuffixLabel());
        $this->assertFieldHelperText($formComponents, 'data.contactor', '用于订单、售后和店铺联系信息展示。');
        $this->assertFieldHelperText($formComponents, 'data.phone', '请输入可联系到店铺负责人的电话号码。');
    }

    private function assertFieldHelperText(mixed $form, string $field, string $expectedText): void
    {
        $fieldComponent = $form->getComponentByStatePath(
            $field,
            withHidden: true,
            withAbsoluteStatePath: true,
        );

        $this->assertInstanceOf(Field::class, $fieldComponent);

        $belowContentComponents = $fieldComponent
            ->getChildSchema(Field::BELOW_CONTENT_SCHEMA_KEY)
            ?->getComponents() ?? [];

        $this->assertCount(1, $belowContentComponents);
        $this->assertSame($expectedText, $belowContentComponents[0]->getContent());
    }

    private function authenticateTenantPanel(): void
    {
        $tenant = Tenant::factory()->create();
        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
    }
}
