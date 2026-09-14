<?php

namespace Tests\Feature\System;

use App\Enums\System\ScheduleRunStatus;
use App\Filament\Backend\Clusters\Setting\Pages\Dashboard;
use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Pages\ManageScheduleRunLogs;
use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Pages\ViewScheduleRunLog;
use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\ScheduleRunLogResource;
use App\Filament\Backend\Clusters\Setting\Widgets\ScheduleRunOverviewWidget;
use App\Models\System\Administrator;
use App\Models\System\ScheduleRunLog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Backend 面板展示层冒烟测试
 *
 * 覆盖列表/详情/概览看板的渲染与筛选，替代人工登录核对。
 */
class ScheduleRunLogPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
    }

    public function test_it_renders_the_list_page(): void
    {
        $log = ScheduleRunLog::factory()->create();

        Livewire::test(ManageScheduleRunLogs::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$log]);
    }

    public function test_it_filters_the_list_by_status_and_task(): void
    {
        $success = ScheduleRunLog::factory()->create(['task' => 'app:mall:order-auto-complete']);
        $failed = ScheduleRunLog::factory()->failed()->create(['task' => 'app:user:identity-expire']);

        Livewire::test(ManageScheduleRunLogs::class)
            ->filterTable('status', ScheduleRunStatus::Failed->value)
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$success]);

        Livewire::test(ManageScheduleRunLogs::class)
            ->filterTable('task', 'app:mall:order-auto-complete')
            ->assertCanSeeTableRecords([$success])
            ->assertCanNotSeeTableRecords([$failed]);
    }

    public function test_it_flags_a_successful_run_with_failed_items(): void
    {
        $log = ScheduleRunLog::factory()->create(['context' => ['completed' => 3, 'failed' => 2]]);

        Livewire::test(ManageScheduleRunLogs::class)
            ->assertCanSeeTableRecords([$log])
            ->assertSee('有失败明细');
    }

    public function test_it_renders_the_view_page(): void
    {
        $log = ScheduleRunLog::factory()->failed('[RuntimeException] boom')->create([
            'context' => ['completed' => 3, 'failed' => 2],
        ]);

        Livewire::test(ViewScheduleRunLog::class, ['record' => $log->getRouteKey()])
            ->assertOk()
            ->assertSee('订单自动完成')
            ->assertSee('boom')
            ->assertSee('completed');
    }

    public function test_it_renders_the_overview_dashboard(): void
    {
        ScheduleRunLog::factory()->create();
        ScheduleRunLog::factory()->failed()->create();
        ScheduleRunLog::factory()->running()->create();

        // 看板页把概览 Widget 作为懒加载子组件渲染，跨组件断言只在 Widget 自身上做
        Livewire::test(Dashboard::class)->assertOk();

        Livewire::test(ScheduleRunOverviewWidget::class)
            ->assertOk()
            ->assertSee('今日执行次数')
            ->assertSee('今日成功率')
            ->assertSee('执行中任务')
            ->assertSee('最近失败任务');
    }

    public function test_the_resource_has_no_create_or_edit_pages(): void
    {
        $pages = ScheduleRunLogResource::getPages();

        $this->assertSame(['index', 'view'], array_keys($pages));
    }
}
