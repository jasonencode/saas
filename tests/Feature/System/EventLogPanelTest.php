<?php

namespace Tests\Feature\System;

use App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Pages\ManageEventLogs;
use App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Pages\ViewEventLog;
use App\Models\System\Administrator;
use App\Models\System\EventLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 日志中心后台页面冒烟测试
 */
class EventLogPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
    }

    public function test_it_renders_the_event_log_list(): void
    {
        $orderLog = EventLog::create([
            'event' => 'order.paid',
            'action' => '订单支付',
            'payload' => ['status' => 'paid'],
        ]);
        $refundLog = EventLog::create([
            'event' => 'refund.completed',
            'action' => '退款完成',
        ]);

        Livewire::test(ManageEventLogs::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$orderLog, $refundLog]);
    }

    public function test_it_filters_by_event_prefix(): void
    {
        $orderLog = EventLog::create(['event' => 'order.paid', 'action' => '订单支付']);
        $refundLog = EventLog::create(['event' => 'refund.completed', 'action' => '退款完成']);

        Livewire::test(ManageEventLogs::class)
            ->filterTable('event', ['event' => 'order.'])
            ->assertCanSeeTableRecords([$orderLog])
            ->assertCanNotSeeTableRecords([$refundLog]);
    }

    public function test_it_renders_the_event_log_detail(): void
    {
        $log = EventLog::create([
            'event' => 'order.paid',
            'action' => '订单支付',
            'payload' => ['status' => 'paid'],
        ]);

        Livewire::test(ViewEventLog::class, ['record' => $log->getKey()])
            ->assertOk()
            ->assertSee('order.paid')
            ->assertSee('订单支付');
    }
}
