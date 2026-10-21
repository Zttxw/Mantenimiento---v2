<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarAndModulesNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_renders_new_unified_sidebar_for_coordinador(): void
    {
        $coordinador = User::create([
            'username' => 'jeanpier',
            'email' => 'jeanpier@mdsj.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $coordinador->assignRole(RoleEnum::COORDINADOR_OTI->value);

        $response = $this->actingAs($coordinador)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('SIGAT OTI');
        $response->assertDontSee('Coordinador OTI');
        $response->assertSee('Soporte y Tickets');
        $response->assertSee('Campañas');
        $response->assertSee('Inventario');
        $response->assertSee('Reportes');
        $response->assertSee('Configuración');
    }

    public function test_sidebar_renders_for_tecnico(): void
    {
        $tecnico = User::create([
            'username' => 'tecnico_test',
            'email' => 'tecnico@mdsj.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $tecnico->assignRole(RoleEnum::TECNICO->value);

        $response = $this->actingAs($tecnico)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Soporte y Tickets');
        $response->assertSee('Campañas');
        $response->assertSee('Inventario');
    }

    public function test_sidebar_renders_for_auxiliar(): void
    {
        $auxiliar = User::create([
            'username' => 'auxiliar_test',
            'email' => 'auxiliar@mdsj.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $auxiliar->assignRole(RoleEnum::AUXILIAR_TI->value);

        $response = $this->actingAs($auxiliar)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Soporte y Tickets');
        $response->assertSee('Inventario');
    }
}
