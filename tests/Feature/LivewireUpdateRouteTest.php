<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LivewireUpdateRouteTest extends TestCase
{
    public function test_livewire_update_route_uses_fixed_path(): void
    {
        $this->assertSame('/livewire/update', app('livewire')->getUpdateUri());

        $route = Route::getRoutes()->getByName('livewire.update');

        $this->assertNotNull($route);
        $this->assertSame('livewire/update', $route->uri());
        $this->assertContains('POST', $route->methods());
    }
}
