<?php

namespace Tests\Feature\Mall;

use App\Filament\Backend\Clusters\Mall\Resources\Regions\Pages\ManageRegions;
use App\Models\Mall\Region;
use App\Models\System\Administrator;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RegionTreeTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Administrator::factory()->create(), 'backend');
        Filament::setCurrentPanel('backend');
    }

    public function test_it_renders_root_regions_in_the_tree_table(): void
    {
        [$province, $city] = $this->makeRegions();

        Livewire::test(ManageRegions::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$province])
            ->assertCanNotSeeTableRecords([$city]);
    }

    public function test_it_shows_children_after_expanding_a_root_region(): void
    {
        [$province, $city] = $this->makeRegions();

        Livewire::test(ManageRegions::class)
            ->call('toggleRowExpansion', (string) $province->getKey())
            ->assertCanSeeTableRecords([$province, $city]);
    }

    public function test_it_keeps_zero_parent_id_when_editing_a_root_region(): void
    {
        [$province] = $this->makeRegions();

        Livewire::test(ManageRegions::class)
            ->mountAction(TestAction::make(EditAction::class)->table($province))
            ->assertSchemaStateSet(['parent_id' => 0]);
    }

    /**
     * @return array{0: Region, 1: Region} 省级与市级地区
     */
    private function makeRegions(): array
    {
        DB::table('regions')->insert([
            [
                'id' => 110000,
                'parent_id' => 0,
                'name' => '北京市',
                'level' => 'p',
                'sort' => 0,
            ],
            [
                'id' => 110100,
                'parent_id' => 110000,
                'name' => '北京市',
                'level' => 'c',
                'sort' => 0,
            ],
        ]);

        return [Region::findOrFail(110000), Region::findOrFail(110100)];
    }
}
