<?php

namespace Tests\Feature\Finance;

use App\Filament\Backend\Clusters\Finance\Resources\Refunds\Pages\ManageRefunds;
use App\Models\Finance\PaymentRefund;
use App\Models\System\Administrator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 退款申请人列搜索
 */
class PaymentRefundApplicantSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_applicant_search_uses_creator_relation_and_can_filter(): void
    {
        $admin = Administrator::factory()->superAdmin()->create(['username' => 'reviewer']);

        $refund = PaymentRefund::factory()->create([
            'created_by_type' => $admin->getMorphClass(),
            'created_by_id' => $admin->getKey(),
        ]);

        $other = PaymentRefund::factory()->create();

        $this->actingAs($admin, 'backend');
        Filament::setCurrentPanel('backend');

        Livewire::test(ManageRefunds::class)
            ->searchTable('reviewer')
            ->assertOk()
            ->assertCanSeeTableRecords([$refund])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
